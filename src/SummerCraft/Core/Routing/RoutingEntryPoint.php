<?php

namespace SummerCraft\Core\Routing;

class RoutingEntryPoint
{
    /**
     * @var string[]
     */
    private array $middlewareServiceNames = [];

    private ?string $controllerName;

    private string $methodName;

    /**
     * @var string[]
     */
    private array $methodParams;

    /**
     * @param string $controllerName
     * @param string $methodName
     * @param string[] $methodParams
     */
    public function __construct(
        string $controllerName,
        string $methodName,
        array $methodParams
    ) {
        $this->controllerName = $controllerName;
        $this->methodName = $methodName;
        $this->methodParams = $methodParams;
    }

    /**
     * @param string[] $middlewareServiceNames
     * @return void
     */
    public function setMiddlewares(array $middlewareServiceNames): void
    {
        $this->middlewareServiceNames = $middlewareServiceNames;
    }

    /**
     * @return string[]
     */
    public function getMiddlewareServiceNames(): array
    {
        return $this->middlewareServiceNames;
    }

    public function getControllerName(): string
    {
        return $this->controllerName;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    /**
     * @return string[]
     */
    public function getMethodParams(): array
    {
        return $this->methodParams;
    }
}
