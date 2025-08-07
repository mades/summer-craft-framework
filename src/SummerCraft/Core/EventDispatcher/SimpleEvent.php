<?php

namespace SummerCraft\Core\EventDispatcher;

class SimpleEvent implements Event
{
    private string $eventName;

    private array $data;

    public function __construct(string $eventName, array $data)
    {
        $this->eventName = $eventName;
        $this->data = $data;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
