<?php

namespace SummerCraft\Core\Routing\Resolver;

use SummerCraft\Core\Routing\RoutingEntryPoint;

interface RoutingResolver
{
    public function getRoutingEntryPoint(array $uriMatchData): ?RoutingEntryPoint;
}
