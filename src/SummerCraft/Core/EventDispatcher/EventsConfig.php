<?php

namespace SummerCraft\Core\EventDispatcher;

use SummerCraft\Core\ComponentManaging\LifeCycle\SharedComponent;

class EventsConfig implements SharedComponent
{
    /**
     * @var array <event name, Eve>
     */
    public array $events = [];
}