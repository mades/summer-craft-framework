<?php

namespace SummerCraft\Core\ConfigLoader;

use SummerCraft\Core\ComponentManaging\ComponentHolder;
use SummerCraft\Core\ComponentManaging\Config\ComponentConfig;
use SummerCraft\Core\ComponentManaging\Config\Config;
use SummerCraft\Core\ComponentManaging\LifeCycle\SharedComponent;
use SummerCraft\Core\Context\ApplicationContext;

abstract class CoreConfigLoader implements SharedComponent
{
    public function __construct(
        protected ComponentHolder $componentHolder,
        protected ApplicationContext $context,
    ) {
    }

    public function load(): void
    {
        $config = $this->componentHolder->get(Config::class, null);
        $config->services[\SummerCraft\Core\Request\Request::class] = ComponentConfig::forClass(\SummerCraft\Core\Request\DefaultRequest::class);
        $config->services[\SummerCraft\Core\Response\Response::class] = ComponentConfig::forClass(\SummerCraft\Core\Response\DefaultResponse::class);
        $config->services[\SummerCraft\Core\EventDispatcher\EventDispatcher::class] = ComponentConfig::forClass(\SummerCraft\Core\EventDispatcher\DefaultEventDispatcher::class);
    }

    public function initialize(): void
    {
        if (!isset($_SERVER['DOCUMENT_ROOT'])) {
            $_SERVER['DOCUMENT_ROOT'] = $this->context->getPublicPath();
        }
    }
}
