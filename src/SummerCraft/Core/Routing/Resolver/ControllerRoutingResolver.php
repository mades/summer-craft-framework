<?php

namespace SummerCraft\Core\Routing\Resolver;

use SummerCraft\Core\Routing\RoutingEntryPoint;

/**
 * Auto-found entry point by controller like:
 * /some1/some2/some3/some4/some5 -> \Some1Controller::some2Action(some3, some4)
 */
class ControllerRoutingResolver implements RoutingResolver
{
    private const METHOD_POSTFIX = 'Action';
    private const METHOD_SNAKE_POSTFIX = '_action';
    private const DEFAULT_METHOD_NAME = 'default';

    private string $controllerName;

    /**
     * @var string[]
     */
    private array $replaceParts = [];

    private array $disallowedParts = [];

    /**
     * Excemple: /users/user(:num)/(:any) we need count all (:num) will be 1
     */
    private int $variablesCount = 0;

    private string $methodPostfix = self::METHOD_POSTFIX;

    private function __construct(string $controllerName)
    {
        $this->controllerName = $controllerName;
    }

    public static function camelBased(string $className, int $variablesCount = 0): self
    {
        $resolver = new self($className);
        $resolver->disallowedParts = ['__Hyphen__', '__Dot__'];
        $resolver->replaceParts = ['-' => '__Hyphen__', '.' => '__Dot__'];
        $resolver->variablesCount = $variablesCount;
        return $resolver;
    }

    public static function snakeBased(string $className, int $variablesCount = 0): self
    {
        $resolver = new self($className);
        $resolver->methodPostfix = self::METHOD_SNAKE_POSTFIX;
        $resolver->disallowedParts = ['__hyphen__', '__dot__'];
        $resolver->replaceParts = ['-' => '__hyphen__', '.' => '__dot__'];
        $resolver->variablesCount = $variablesCount;
        return $resolver;
    }

    public function getRoutingEntryPoint(array $uriMatchData): ?RoutingEntryPoint
    {
        $params = [];
        for ($i = 1; $i <= $this->variablesCount; $i++) {
            $params[] = $uriMatchData[$i];
            unset($uriMatchData[$i]);
        }
        $uriMatchData = array_values($uriMatchData);

        $segments = explode('/', $uriMatchData[1] ?? '');
        if ($segments[0] === '') {
            array_shift($segments);
        }

        if (!class_exists($this->controllerName, true)) {
            return null;
        }

        $testMethodName = self::DEFAULT_METHOD_NAME . $this->methodPostfix;
        if (!empty($segments)) {
            $testMethodName = $segments[0];
            foreach ($this->replaceParts as $replacePartsKey => $replacePartsValue) {
                $testMethodName = str_replace($replacePartsKey, $replacePartsValue, $testMethodName);
            }
            $testMethodName .= $this->methodPostfix;
            array_shift($segments);
        }

        foreach ($this->disallowedParts as $disallowedPart) {
            if (str_contains($testMethodName, $disallowedPart)) {
                return null;
            }
        }

        if (!method_exists($this->controllerName, $testMethodName)) {

            return null;
        }



        foreach ($segments as $segment) {
            $params[] = $segment;
        }

        return new RoutingEntryPoint(
            $this->controllerName,
            $testMethodName,
            $params
        );
    }
}
