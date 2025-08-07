<?php

namespace SummerCraft\Core\Routing;

/**
 * Some logic to run before the controller is created and started.
 * If the run method returns false, then the controller will not start.
 *
 * There may be authentication checks here or changes in request scope services etc.
 */
interface Middleware
{
    public function run(): bool;
}
