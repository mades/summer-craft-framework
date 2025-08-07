<?php

namespace SummerCraft\Core\Request;

use SummerCraft\Core\ComponentManaging\LifeCycle\SharedComponent;

class RequestConfig implements SharedComponent
{
    /**
     * Allowed URL Characters
     */
    public string $permittedUriChars = 'A-zА-я0-9~%.:_\-\+\,';
}