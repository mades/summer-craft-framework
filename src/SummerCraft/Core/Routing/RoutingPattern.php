<?php

namespace SummerCraft\Core\Routing;

use SummerCraft\Core\Request\Request;
use SummerCraft\Core\Routing\Resolver\RoutingResolver;

class RoutingPattern
{
    private const URI_PATTERN_REG_FROM = [':any', ':num', ':all'];
    private const URI_PATTERN_REG_TO = ['[^/]+', '[0-9]+', '.+'];

    /**
     * @var int[]
     */
    private ?array $allowMethods = null;

    /**
     * @var string[]
     */
    private ?array $domains = null;

    /**
     * @var string[]
     */
    private array $uriPatterns = [];

    /**
     * @var string[]
     */
    private array $middlewareServiceNames = [];

    private RoutingResolver $routingEntryPoint;

    public function __construct(RoutingResolver $routingEntryPoint)
    {
        $this->routingEntryPoint = $routingEntryPoint;
    }

    public static function resolveWith(RoutingResolver $routingResolver): self
    {
        return new self($routingResolver);
    }

    public function forMethod(int $allowMethod): RoutingPattern
    {
        $this->allowMethods[] = $allowMethod;
        return $this;
    }

    public function forDomain(?string $domain): RoutingPattern
    {
        if ($domain === null) {
            return $this;
        }
        $this->domains[] = $domain;
        return $this;
    }

    public function forDomains(array $domains): RoutingPattern
    {
        $this->domains[] = $domains;
        return $this;
    }

    public function forUriPattern(string $uriPattern): RoutingPattern
    {
        $this->uriPatterns[] = $uriPattern;
        return $this;
    }

    public function forUriPatterns(array $uriPatterns): RoutingPattern
    {
        $this->uriPatterns = $uriPatterns;
        return $this;
    }

    /**
     * @param string[] $middlewareServiceNames
     * @return $this
     */
    public function withMiddlewares(array $middlewareServiceNames): RoutingPattern
    {
        $this->middlewareServiceNames = $middlewareServiceNames;
        return $this;
    }

    public function check(Request $request): ?RoutingEntryPoint
    {
        $allowByMethod = false;
        $requestMethod = $request->getMethod();
        if ($this->allowMethods === null) {
            $allowByMethod = true;
        } else {
            foreach ($this->allowMethods as $allowMethod) {
                if ($allowMethod === $requestMethod) {
                    $allowByMethod = true;
                    break;
                }
            }
        }
        if (!$allowByMethod) {
            // TODO
            // Debugger::log(['notAllowByMethod', $requestMethod, $this->allowMethods]);
            return null;
        }

        $allowedByDomain = false;
        $requestDomain = $request->getDomain();
        if ($this->domains === null) {
            $allowedByDomain = true;
        } else {
            foreach ($this->domains as $allowDomain) {
                if ($allowDomain === $requestDomain) {
                    $allowedByDomain = true;
                    break;
                }
            }
        }
        if (!$allowedByDomain) {
            //Debugger::log(['notAllowedByDomain', $requestMethod, $this->allowMethods]);
            return null;
        }

        foreach ($this->uriPatterns as $uriPattern) {
            $uriRegPattern = str_replace(self::URI_PATTERN_REG_FROM, self::URI_PATTERN_REG_TO, $uriPattern);

            $matches = [];
            if (!preg_match('#^'.$uriRegPattern.'$#', $request->getSegmentsUri(), $matches)) {
//                Debugger::log(['notAllowedByPatten', [
//                    '#^'.$uriRegPattern.'$#',  $request->getSegmentsUri()
//                ]]);
                continue;
            }

//            Debugger::log(['allowedByPatten', [
//                '#^'.$uriRegPattern.'$#',  $request->getSegmentsUri()
//            ]]);

            $result = $this->routingEntryPoint->getRoutingEntryPoint($matches);
            if ($result) {
                $result->setMiddlewares($this->middlewareServiceNames);
                return $result;
            }
        }

        return null;
    }

    public function toKeyString(): string
    {
        return
            ($this->allowMethods ? implode(',', $this->allowMethods) : '')
            . '|'
            . ($this->domains ? implode(',', $this->domains) : '')
            . '|'
            . implode(',', $this->uriPatterns);
    }
}
