<?php

namespace SummerCraft\Core\Routing\Resolver;

use SummerCraft\Core\Routing\RoutingEntryPoint;

class MethodRoutingResolver implements RoutingResolver
{
    public string $controllerClassName;

    public string $methodName;

    private function __construct(string $controllerClassName, string $methodName)
    {
        $this->controllerClassName = $controllerClassName;
        $this->methodName = $methodName;
    }

    public static function for(string $className, string $methodName): self
    {
        return new self($className, $methodName);
    }

    public function getRoutingEntryPoint(array $uriMatchData): ?RoutingEntryPoint
    {
        unset($uriMatchData[0]);
        return new RoutingEntryPoint(
            $this->controllerClassName,
            $this->methodName,
            array_values($uriMatchData)
        );
    }
}
