<?php

namespace SummerCraft\Core\EventDispatcher;

use RuntimeException;
use SummerCraft\Core\ComponentManaging\LifeCycle\SharedComponent;
use SummerCraft\Core\ComponentManaging\RequestScope;

class DefaultEventDispatcher implements EventDispatcher, SharedComponent
{
    private RequestScope $requestScope;

    /**
     * @var string[][] <EventName, Index, ServiceName>
     */
    protected array $subscriptions = [];

    public function __construct(RequestScope $requestScope, EventsConfig $eventsConfig)
    {
        $this->requestScope = $requestScope;
        foreach ($eventsConfig->events as $event) {
            $this->subscribe($event[0], $event[1]);
        }
    }

    public function subscribe(string $eventName, string $subscriberServiceName): void
    {
        if (!isset($this->subscriptions[$eventName])) {
            $this->subscriptions[$eventName] = [];
        }
        $this->subscriptions[$eventName][] = $subscriberServiceName;
    }

    public function fire(Event $event): void
    {
        $eventName = $event->getEventName();
        if (!isset($this->subscriptions[$eventName])) {
            return;
        }
        foreach ($this->subscriptions[$eventName] as $subscriberServiceName) {
            $subscriber = $this->requestScope->get($subscriberServiceName);
            if (!$subscriber instanceof EventSubscriber) {
                throw new RuntimeException(sprintf('Invalid implementation. Expected %s, got %s', EventSubscriber::class, get_class($subscriber)));
            }
            $subscriber->catchEvent($event);
        }
    }
}
