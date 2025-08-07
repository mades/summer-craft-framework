<?php

namespace SummerCraft\Core\Routing;

use Psr\Log\LoggerInterface;
use RuntimeException;
use SummerCraft\Core\Autoloader;
use SummerCraft\Core\BenchmarkHolder;
use SummerCraft\Core\ComponentManaging\LifeCycle\RequestScopeComponent;
use SummerCraft\Core\ComponentManaging\RequestScope;
use SummerCraft\Core\ExceptionProcessing\ThrowableContext;
use SummerCraft\Core\Request\Request;
use SummerCraft\Core\Routing\Exception\BadRequestException;
use Throwable;

class Router implements RequestScopeComponent
{
    private LoggerInterface $log;

    public function __construct(
        private RequestScope $requestScope,
        private RouterConfig $config,
        private BenchmarkHolder $benchmark
    ) {
        if ($this->requestScope->has(LoggerInterface::class)) {
            $this->log = $this->requestScope->get(LoggerInterface::class);
        }
    }

    public function route(RequestScope $requestScope): void
    {
        try {
            $request = $requestScope->get(Request::class);
            foreach ($this->config->routingPatterns as $routingPattern) {
                $routingEntryPoint = $routingPattern->check($request);
                if ($routingEntryPoint === null) {
                    continue;
                }

                $this->routeExecute($requestScope, $routingEntryPoint);
                return;
            }

            if (isset($this->log)) {
                $this->log->notice('Client 404 on Page not found: ' . $request->getUri());
            }

            $this->routeExecute($requestScope, $this->config->entryPointForError404);

        } catch (BadRequestException $exception) {
            if (isset($this->log)) {
                $this->log->warning('Client Bad Request', [
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'fileLine' => $exception->getLine(),
                    'backtrace' => $exception->getTraceAsString(),
                ]);
            }

            $requestScope->get(ThrowableContext::class)->setThrowable($exception);
            $this->routeExecute($requestScope, $this->config->entryPointForError404);
        } catch (Throwable $throwable) {
            if (isset($this->log)) {
                $this->log->warning('Client 500 on Internal server Error', [
                    'message' => $throwable->getMessage(),
                    'file' => $throwable->getFile(),
                    'fileLine' => $throwable->getLine(),
                    'backtrace' => $throwable->getTraceAsString(),
                ]);
            }

            $requestScope->get(ThrowableContext::class)->setThrowable($throwable);
            $this->routeExecute($requestScope, $this->config->entryPointForError500);
        }
    }

    private function routeExecute(RequestScope $requestScope, RoutingEntryPoint $routingEntryPoint): void
    {
        $this->benchmark->point('CreateMiddlewares');

        foreach ($routingEntryPoint->getMiddlewareServiceNames() as $middlewareServiceName) {
            $middleware = $requestScope->get($middlewareServiceName);
            if (!$middleware instanceof Middleware) {
                throw new RuntimeException(
                    sprintf('Middleware service [%s] should implement [%s]', $middlewareServiceName, Middleware::class)
                );
            }
            if (!$middleware->run()) {
                return;
            }
        }

        $this->benchmark->point('CreateController');

        $controller = $requestScope->get($routingEntryPoint->getControllerName());

        $this->benchmark->point('RunControllerAction');

        $controllerMethod = $routingEntryPoint->getMethodName();
        $controller->$controllerMethod(...$routingEntryPoint->getMethodParams());

        $this->benchmark->point('EndControllerAction');
    }
}
