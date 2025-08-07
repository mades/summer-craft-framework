<?php

namespace SummerCraft\Core\Routing;

use SummerCraft\Core\ComponentManaging\LifeCycle\SharedComponent;
use SummerCraft\Core\Response\SpecificResponseHandler;

class RouterConfig implements SharedComponent
{
    public RoutingEntryPoint $entryPointForError400;
    public RoutingEntryPoint $entryPointForError404;
    public RoutingEntryPoint $entryPointForError500;

    /**
     * @var RoutingPattern[]
     */
    public array $routingPatterns = [];

    public function __construct()
    {
        /** @see SpecificResponseHandler::errorBadRequest() */
        $this->entryPointForError400 = new RoutingEntryPoint(SpecificResponseHandler::class, 'errorBadRequest', []);
        /** @see SpecificResponseHandler::errorNotFound() */
        $this->entryPointForError404 = new RoutingEntryPoint(SpecificResponseHandler::class, 'errorNotFound', []);
        /** @see SpecificResponseHandler::errorServerError() */
        $this->entryPointForError500 = new RoutingEntryPoint(SpecificResponseHandler::class, 'errorServerError', []);
    }

    public function addPattern(RoutingPattern $pattern): self
    {
        $this->routingPatterns[$pattern->toKeyString()] = $pattern;
        return $this;
    }

    /**
     * @param RoutingPattern[] $patterns
     * @return $this
     */
    public function addPatterns(array $patterns): self
    {
        foreach ($patterns as $pattern) {
            $this->routingPatterns[$pattern->toKeyString()] = $pattern;
        }
        return $this;
    }


}