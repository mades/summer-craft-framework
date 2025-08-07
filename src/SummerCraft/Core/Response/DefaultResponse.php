<?php

namespace SummerCraft\Core\Response;

use RuntimeException;
use SummerCraft\Core\Autoloader;
use SummerCraft\Core\BenchmarkHolder;
use SummerCraft\Core\ComponentManaging\LifeCycle\RequestScopeComponent;
use SummerCraft\Core\ComponentManaging\RequestScope;
use SummerCraft\Core\Request\Request;

class DefaultResponse implements Response, RequestScopeComponent
{
    protected array $finalHeaders = [];

    protected array $finalCookies = [];

    protected string $finalOutput = '';

    protected ResponseConfig $config;

    protected int $initLevel;

    protected Request $request;

    protected RequestScope $scope;

    private bool $sended = false;

    public function __construct(RequestScope $requestScope, ResponseConfig $config, Request $request)
    {
        $this->initLevel = ob_get_level();
        $this->scope = $requestScope;
        $this->config = $config;
        $this->request = $request;
    }

    public function append(string $output): Response
    {
        $this->finalOutput .= $output;

        return $this;
    }

    /**
     * @param string[] $headerStrings
     * @param bool $replace
     * @param int|null $code
     */
    public function setHeaders(array $headerStrings, bool $replace = false, int $code = null): void
    {
        foreach ($headerStrings as $key => $headerString) {
            $this->finalHeaders[] = [$headerString, $replace, $code];
        }
    }

    public function denyIframe(): void
    {
        $this->setHeaders(['X-Frame-Options: DENY']);
    }

    public function setCookie(string $name, ?string $value, int $expireTime, string $path, string $domain): void
    {
        $this->finalCookies[] = [$name, $value, $expireTime, $path, $domain];
    }

    private function sendHeaders(): void
    {
        foreach ($this->finalHeaders as $header) {
            [$headerContent, $replace, $code] = $header;
            if ($code !== null) {
                header($headerContent, $replace, $code);
            } else {
                header($headerContent, $replace);
            }
        }
        $this->finalHeaders = [];
    }

    private function sendCookies(): void
    {
        foreach ($this->finalCookies as $finalCookie) {
            [$name, $value, $expireTime, $path, $domain] = $finalCookie;
            \setcookie($name, $value, $expireTime, $path, $domain);
        }
    }

    public function send(): void
    {
        if ($this->sended) {
            return;
        }
        $this->sendHeaders();
        $this->sendCookies();
        if ($this->config->profiler) {
            /** @var BenchmarkHolder $benchmarkHolder */
            $benchmarkHolder = $this->scope->get(BenchmarkHolder::class);
            $benchmarkHolder->point('ResponseSend');
            if (strpos($this->finalOutput, '{#result_time_table}') !== false) {
                $this->finalOutput = str_replace('{#result_time_table}', $benchmarkHolder->benchmarkTotalTimeTable(), $this->finalOutput);
            }
            if (strpos($this->finalOutput, '{#result_class_table}') !== false) {
                if ($this->scope->has(Autoloader::class)) {
                    $autoloader = $this->scope->get(Autoloader::class);
                    $this->finalOutput = str_replace('{#result_class_table}', $benchmarkHolder->benchmarkTotalLoadedTable($autoloader->getLoadedClasses()), $this->finalOutput);
                } else {
                    $this->finalOutput = str_replace('{#result_class_table}', 'No autoloader found', $this->finalOutput);
                }
            }
            if (strpos($this->finalOutput, '{#result_time}') !== false) {
                $this->finalOutput = str_replace('{#result_time}', $benchmarkHolder->elapsedString('BEFORE_SEND_RESPONSE'), $this->finalOutput);
            }
            if (strpos($this->finalOutput, '{#result_memory}') !== false) {
                $this->finalOutput = str_replace('{#result_memory}', $benchmarkHolder->usedMemoryAsString(), $this->finalOutput);
            }
        }

        echo $this->finalOutput;
    }

    /**
     * @param int $code
     * @param string|null $text
     */
    public function setStatus(int $code, ?string $text = null): void
    {
        if ($text === null) {
            if (isset(Response::HTTP_CODES[$code])) {
                $text = Response::HTTP_CODES[$code];
            } else {
                throw new RuntimeException(
                    'No status text available. Please check your status code number or supply your own message text.'
                );
            }
        }

        if ($this->request->isCli()) {
            $this->setHeaders(["Status: $code $text"], true, $code);
        } else {
            $protocol = $this->request->getProtocol();
            $this->setHeaders(["$protocol $code $text"], true, $code);
        }
    }

    /**
     * Redirect on other page
     * @param string $url
     * @param boolean $temperatory Is 302 Moved Temperatory
     */
    public function location(string $url, bool $temperatory = false): void
    {
        if($temperatory === true){
            $this->setStatus(302, 'Moved Temporarily');
        } else {
            $this->setStatus(301, 'Moved Permanently');
        }
        $this->setHeaders(["Location: $url"], true);
    }

    /**
     * @return string[]
     */
    public function getHeaders(): array
    {
        return $this->finalHeaders;
    }

    public function getCookies(): array
    {
        return $this->finalCookies;
    }

    public function getContent(): string
    {
        return $this->finalOutput;
    }
}
