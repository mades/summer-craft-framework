<?php

namespace SummerCraft\Core\ComponentManaging;

use SummerCraft\Core\Request\RequestIdentity;

class RequestScope
{
    private RequestIdentity $identity;

    private ComponentHolder $componentHolder;

    public function __construct(ComponentHolder $componentHolder)
    {
        $this->identity = RequestIdentity::createUnique();
        $this->componentHolder = $componentHolder;
        $this->componentHolder->set(RequestScope::class, $this->identity, $this);
    }

    /**
     * Get component by key
     * @template T
     * @param class-string<T> $componentName Component name or className
     * @return T
     */
    public function get(string $componentName): object
    {
        return $this->componentHolder->get($componentName, $this->getIdentity());
    }

    public function has(string $componentName): bool
    {
        return $this->componentHolder->has($componentName);
    }

    public function set(string $componentName, object $component): void
    {
        $this->componentHolder->set($componentName, $this->getIdentity(), $component);
    }

    public function getIdentity(): RequestIdentity
    {
        return $this->identity;
    }
}
