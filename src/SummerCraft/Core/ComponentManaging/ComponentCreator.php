<?php

namespace SummerCraft\Core\ComponentManaging;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use RuntimeException;
use SummerCraft\Core\ComponentManaging\LifeCycle\SharedComponent;
use SummerCraft\Core\ComponentManaging\LifeCycle\TransientComponent;
use SummerCraft\Core\Request\RequestIdentity;
use SummerCraft\Core\Exception\ComponentException;
use Throwable;

/**
 * Create component throw reflection. for child components calls ComponentHolder
 *
 * Checks scope of components:
 * Shared components can have only shared children
 * Non-shared components can have shared, non-shared
 * Request-scoped components can have shared, non-shared and request-scoped children
 * So: Shared components can not have request-scoped and non-shared children
 *
 */
class ComponentCreator
{
    private array $serviceStack = [];

    private int $recursiveCall = 0;

    public function createComponentWithReflection(
        ComponentHolder $serviceHolder,
        ComponentCreator $recursiveCreator,
        string $className,
        ?RequestIdentity $requestIdentity
    ) {
        if ($recursiveCreator->recursiveCall > 127) {
            throw ComponentException::onRecursiveComponentCreation($className, $this->serviceStack);
        }

        $this->serviceStack[$className] = true;

        try {
            /**
             * Investigating of usage Reflection for autowiring classes has shown that it get small impact on perfomance
             * https://github.com/brainfoolong/php-reflection-performance-tests
             *
             * In addition, a benchmark comparison of the old approach and the new approach showed
             * that the response time remained generally the same.
             */
            $reflectionClass = new ReflectionClass($className);

            $resultParams = [];
            $reflectionConstructor = $reflectionClass->getConstructor();
            if ($reflectionConstructor) {
                foreach ($reflectionConstructor->getParameters() as $parameter) {
                    if ($parameter->isOptional()) {
                        $resultParams[] = $parameter->getDefaultValue();
                        continue;
                    }
                    $parameterType = $parameter->getType();
                    if (!$parameterType instanceof ReflectionNamedType) {
                        throw new RuntimeException(sprintf(
                            'Service [%s] can not be created. Reason: Parameter type is not specified. %s',
                            $className,
                            json_encode($this->serviceStack, JSON_PRETTY_PRINT)
                        ));
                    }

                    $recursiveCreator->recursiveCall++;
                    $service = $serviceHolder->get($parameterType->getName(), $requestIdentity,  $recursiveCreator);
                    if ($service === null) {
                        throw new RuntimeException(sprintf(
                            'Service [%s] can not be created. Reason: Service [%s] not specified. %s',
                            $className,
                            $parameterType->getName(),
                            json_encode($this->serviceStack, JSON_PRETTY_PRINT)
                        ));
                    }
                    $resultParams[] = $service;
                }
            }
        } catch (ReflectionException $exception) {
            throw new RuntimeException(sprintf(
                'Service [%s] can not be created. Reason: %s. Current AutoWire Classes: %s',
                $className,
                $exception->getMessage(),
                json_encode(array_keys($this->serviceStack), JSON_PRETTY_PRINT)
            ));
        }

        unset($this->serviceStack[$className]);

        try {
            $result = new $className(...$resultParams);

            if ($result instanceof SharedComponent) {
                foreach ($resultParams as $resultParam) {
                    if (!$resultParam instanceof SharedComponent && $resultParam instanceof TransientComponent) {
                        throw new RuntimeException(sprintf(
                            "Service (%s) is in SharedScope and has dependency of Service (%s) in RequestBasedScope",
                            $className,
                            gettype($resultParam)
                        ));
                    }
                }
            }

            return $result;
        } catch (Throwable $exception) {
            throw new RuntimeException(sprintf(
                'Service [%s] can not be created. Reason: %s. Current AutoWire Classes: %s',
                $className,
                $exception->getMessage(),
                json_encode(array_keys($this->serviceStack), JSON_PRETTY_PRINT)
            ));
        }
    }

}
