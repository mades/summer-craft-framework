<?php

namespace SummerCraft\Core;

use SummerCraft\Core\ComponentManaging\ComponentHolder;
use SummerCraft\Core\ComponentManaging\Config\Config;
use SummerCraft\Core\ComponentManaging\RequestScope;
use SummerCraft\Core\ConfigLoader\CoreConfigLoader;
use SummerCraft\Core\Context\ApplicationContext;
use SummerCraft\Core\EventDispatcher\EventDispatcher;
use SummerCraft\Core\EventDispatcher\SimpleEvent;
use SummerCraft\Core\ExceptionProcessing\ExceptionProcessingConfig;
use SummerCraft\Core\ExceptionProcessing\ExceptionProcessor;
use SummerCraft\Core\Request\DefaultRequest;
use SummerCraft\Core\Request\Request;
use SummerCraft\Core\Request\RequestConfig;
use SummerCraft\Core\Request\RequestIdentity;
use SummerCraft\Core\Response\Response;
use SummerCraft\Core\Routing\Router;
use Throwable;

final class Application
{
    private static Application $instance;

    private ApplicationContext $applicationContext;

    private ComponentHolder $componentHolder;

    public static function getInstance(): Application
    {
        if (!isset(self::$instance)) {
            throw new \RuntimeException("Application not created. It should be created before usage.");
        }
        return self::$instance;
    }

    public static function hasInstance(): bool
    {
        return isset(self::$instance);
    }

    public static function configureErrorHandlers(): void
    {
        ExceptionProcessor::configureDefaultHandlers();
    }

    public static function create(
        ApplicationContext $applicationContext,
        ?Autoloader $codeLoaderInstance = null
    ): Application {
        self::$instance = new self();
        self::$instance->init($applicationContext, $codeLoaderInstance);
        return self::$instance;
    }

    public function init(
        ApplicationContext $applicationContext,
        ?Autoloader $codeLoaderInstance = null
    ): void
    {
        $benchmark = BenchmarkHolder::getInstance();

        $config = new Config();
        $this->applicationContext = $applicationContext;
        $this->componentHolder = new ComponentHolder($config);
        $this->componentHolder->set(Application::class, null, $this);
        $this->componentHolder->set(Config::class, null, $config);
        $this->componentHolder->set(ApplicationContext::class, null, $applicationContext);
        $this->componentHolder->set(BenchmarkHolder::class, null, $benchmark);
        if ($codeLoaderInstance !== null) {
            $this->componentHolder->set(Autoloader::class, null, $codeLoaderInstance);
        }

        /** @var CoreConfigLoader $configLoader Child of CoreConfigLoader */
        $environmentConfigLoaderClass = $applicationContext->getConfigLoader();
        $configLoader = new $environmentConfigLoaderClass($this->componentHolder, $this->applicationContext);
        $configLoader->load();
        $this->initExceptionProcessing();
        $configLoader->initialize();
    }

    private function initExceptionProcessing(): void
    {
        $config = $this->componentHolder->get(ExceptionProcessingConfig::class, null);
        ExceptionProcessor::initErrorConfiguration($this->applicationContext, $config);
    }

    public function getContext(): ApplicationContext
    {
        return $this->applicationContext;
    }

    /**
     * Get shared or no-shared component by key
     * @template T
     * @param class-string<T> $componentName Component name or className
     * @param RequestIdentity|null $requestIdentity Identity of current request scope
     * @return T
     */
    public function get(string $componentName, ?RequestIdentity $requestIdentity = null): object
    {
        return $this->componentHolder->get($componentName, $requestIdentity);
    }

    /**
     * Run the application
     * @param Request|null $request
     * @return Response
     */
    public function run(?Request $request): ?Response
    {
        $requestScope = null;
        try {
            $requestScope = new RequestScope($this->componentHolder);
            $this->componentHolder->set(RequestScope::class, $requestScope->getIdentity(), $requestScope);

            $eventDispatcher = $requestScope->get(EventDispatcher::class);
            $eventDispatcher->fire(new SimpleEvent('request.start', []));

            if ($request === null) {
                $request = new DefaultRequest($this->get(RequestConfig::class));
            }
            $this->componentHolder->set(Request::class, $requestScope->getIdentity(), $request);

            $router = $requestScope->get(Router::class);
            $router->route($requestScope);

            $response = $requestScope->get(Response::class);

            $this->componentHolder->destroyScope($requestScope);

            return $response;
        } catch (Throwable $ex) {
            ExceptionProcessor::defaultProcessException($ex, $requestScope);
            return null;
        }
    }
}
