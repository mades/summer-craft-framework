<?php

namespace SummerCraft\Core\ComponentManaging;

use RuntimeException;
use SummerCraft\Core\ComponentManaging\Config\Config;
use SummerCraft\Core\ComponentManaging\LifeCycle\SharedComponent;
use SummerCraft\Core\ComponentManaging\LifeCycle\TransientComponent;
use SummerCraft\Core\Request\RequestIdentity;
use SummerCraft\Core\Exception\ComponentException;

class ComponentHolder
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @var object[] Container of loaded shared components(services)
     *               BY component(service) name
     */
    private array $sharedComponents = [];

    /**
     * @var object[][] Container of loaded shared components(services)
     *               BY scope name and component(service) name
     */
    private array $scopedComponents = [];

    /**
     * Get component by key.
     * @template T
     * @param class-string<T> $name Key of object
     * @param RequestIdentity|null $requestIdentity
     * @param ComponentCreator|null $recursiveCreator
     * @return T
     */
    public function get(string $name, ?RequestIdentity $requestIdentity, ComponentCreator $recursiveCreator = null)
    {
        $requestScopeId = $requestIdentity ? $requestIdentity->getId() : '';
        if ($requestIdentity !== null && isset($this->scopedComponents[$requestScopeId][$name])) {
            return $this->scopedComponents[$requestScopeId][$name];
        }
        if (isset($this->sharedComponents[$name])) {
            return $this->sharedComponents[$name];
        }

        $serviceConfig = $this->config->services[$name] ?? null;
        $serviceClassName = isset($this->config->services[$name])
            ? $this->config->services[$name]->className
            : $name;

        if ($serviceClassName !== $name) {
            if ($requestIdentity !== null && isset($this->scopedComponents[$requestScopeId][$serviceClassName])) {
                $this->scopedComponents[$requestScopeId][$name] = $this->scopedComponents[$requestScopeId][$serviceClassName];
                return $this->scopedComponents[$requestScopeId][$name];
            }
            if (isset($this->sharedComponents[$serviceClassName])) {
                $this->sharedComponents[$name] = $this->sharedComponents[$serviceClassName];
                return $this->sharedComponents[$name];
            }
        }

        if (is_a($serviceClassName, TransientComponent::class, true)) {
            if ($serviceConfig !== null && $serviceConfig->isCallbackMethodCreation()) {
                $callback = $serviceConfig->callback;
                $obj = $callback($this, $requestIdentity);
                if (!is_a($obj, $serviceClassName)) {
                    throw ComponentException::onServiceValidationNotPassed($name, get_class($obj), $serviceClassName);
                }
                if (!is_a($obj, TransientComponent::class)) {
                    throw ComponentException::onServiceValidationNotPassed($name, get_class($obj), TransientComponent::class);
                }
            } else {
                $recursiveCreator = $recursiveCreator ?: new ComponentCreator();
                $obj = $recursiveCreator->createComponentWithReflection($this, $recursiveCreator, $serviceClassName, $requestIdentity);
            }
            return $obj;
        }

        if (is_a($serviceClassName, SharedComponent::class, true)) {
            if ($serviceConfig !== null && $serviceConfig->isCallbackMethodCreation()) {
                $callback = $serviceConfig->callback;
                $obj = $callback($this, $requestIdentity);
                if (!is_a($obj, $serviceClassName)) {
                    throw ComponentException::onServiceValidationNotPassed($name, get_class($obj), $serviceClassName);
                }
                if (!is_a($obj, SharedComponent::class)) {
                    throw ComponentException::onServiceValidationNotPassed($name, get_class($obj), TransientComponent::class);
                }
            } else {
                $recursiveCreator = $recursiveCreator ?: new ComponentCreator();
                $obj = $recursiveCreator->createComponentWithReflection($this, $recursiveCreator, $serviceClassName, $requestIdentity);
            }
            $this->sharedComponents[$name] = $obj;
            if ($serviceClassName !== $name) {
                $this->sharedComponents[$serviceClassName] = $obj;
            }
            return $obj;
        }
        if ($requestIdentity === null) {
            throw new RuntimeException(sprintf(
                "Trying create component (%s) without Request scope. You need mark component as (%s) or (%s)",
                $serviceClassName,
                SharedComponent::class,
                TransientComponent::class
            ));
        }
        if ($serviceConfig !== null && $serviceConfig->isCallbackMethodCreation()) {
            $callback = $serviceConfig->callback;
            $obj = $callback($this, $requestIdentity);
            if (!is_a($obj, $serviceClassName)) {
                throw ComponentException::onServiceValidationNotPassed($name, get_class($obj), $serviceClassName);
            }
        } else {
            $recursiveCreator = $recursiveCreator ?: new ComponentCreator();
            $obj = $recursiveCreator->createComponentWithReflection($this, $recursiveCreator, $serviceClassName, $requestIdentity);
        }
        $this->scopedComponents[$requestScopeId][$name] = $obj;
        if ($serviceClassName !== $name) {
            $this->scopedComponents[$requestScopeId][$serviceClassName] = $obj;
        }
        return $obj;
    }

    public function set(string $name, ?RequestIdentity $requestIdentity, $object): void
    {
        if ($requestIdentity !== null) {
            $this->scopedComponents[$requestIdentity->getId()][$name] = $object;
        } else {
            $this->sharedComponents[$name] = $object;
        }
    }

    public function has(string $name): bool
    {
        if (isset($this->sharedComponents[$name])) {
            return true;
        }

        $serviceConfig = $this->config->services[$name] ?? null;
        return $serviceConfig !== null;
    }

    public function setSharedComponent(string $name, $object): void
    {
        $this->sharedComponents[$name] = $object;
    }

    public function destroyScope(RequestScope $requestScope): void
    {
        unset($this->scopedComponents[$requestScope->getIdentity()->getId()]);
    }
}
