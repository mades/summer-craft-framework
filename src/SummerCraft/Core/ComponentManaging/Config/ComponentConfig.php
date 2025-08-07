<?php

namespace SummerCraft\Core\ComponentManaging\Config;

class ComponentConfig
{
    public string $className;

    /**
     * @var callable|null
     */
    public $callback;

    public static function forCallback(callable $callback, string $className): self
    {
        $config = new self();
        $config->callback = $callback;
        $config->className = $className;
        return $config;
    }

    public static function forClass(string $className): self
    {
        $config = new self();
        $config->callback = null;
        $config->className = $className;
        return $config;
    }

    public function isCallbackMethodCreation(): bool
    {
        return $this->callback !== null;
    }
}