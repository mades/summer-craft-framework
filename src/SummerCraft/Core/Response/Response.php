<?php

namespace SummerCraft\Core\Response;

/**
 * HTTP Response Interface
 */
interface Response
{
    public const HTTP_CODE_MOVED_PERMANENTLY = 301;
    public const HTTP_CODE_MOVED_FOUND = 302;
    public const HTTP_CODE_BAD_REQUEST = 400;
    public const HTTP_CODE_FORBIDDEN = 403;
    public const HTTP_CODE_NOT_FOUND = 404;
    public const HTTP_CODE_INTERNAL_SERVER_ERROR = 500;


    public const HTTP_CODES = [
        100	=> 'Continue',
        101	=> 'Switching Protocols',

        200	=> 'OK',
        201	=> 'Created',
        202	=> 'Accepted',
        203	=> 'Non-Authoritative Information',
        204	=> 'No Content',
        205	=> 'Reset Content',
        206	=> 'Partial Content',

        300	=> 'Multiple Choices',
        301	=> 'Moved Permanently',
        302	=> 'Found',
        303	=> 'See Other',
        304	=> 'Not Modified',
        305	=> 'Use Proxy',
        307	=> 'Temporary Redirect',

        400	=> 'Bad Request',
        401	=> 'Unauthorized',
        402	=> 'Payment Required',
        403	=> 'Forbidden',
        404	=> 'Not Found',
        405	=> 'Method Not Allowed',
        406	=> 'Not Acceptable',
        407	=> 'Proxy Authentication Required',
        408	=> 'Request Timeout',
        409	=> 'Conflict',
        410	=> 'Gone',
        411	=> 'Length Required',
        412	=> 'Precondition Failed',
        413	=> 'Request Entity Too Large',
        414	=> 'Request-URI Too Long',
        415	=> 'Unsupported Media Type',
        416	=> 'Requested Range Not Satisfiable',
        417	=> 'Expectation Failed',
        422	=> 'Unprocessable Entity',
        426	=> 'Upgrade Required',
        428	=> 'Precondition Required',
        429	=> 'Too Many Requests',
        431	=> 'Request Header Fields Too Large',

        500	=> 'Internal Server Error',
        501	=> 'Not Implemented',
        502	=> 'Bad Gateway',
        503	=> 'Service Unavailable',
        504	=> 'Gateway Timeout',
        505	=> 'HTTP Version Not Supported',
        511	=> 'Network Authentication Required',
    ];

    /**
     * Set Response Status
     * @param int $code
     * @param string|null $text
     */
    public function setStatus(int $code, ?string $text = null): void;

    /**
     * Set Headers
     * array of strings or array of key => values
     * @param array $headerStrings
     * @param bool $replace
     * @param int|null $code
     */
    public function setHeaders(array $headerStrings, bool $replace = false, int $code = null): void;

    /**
     * Append body to response
     * @param string $output
     */
    public function append(string $output): Response;

    /**
     * SetCookie
     * @param string $name
     * @param string $value
     * @param int $expireTime
     * @param string $path
     * @param string $domain
     */
    public function setCookie(string $name, ?string $value, int $expireTime, string $path, string $domain): void;

    /**
     * Build And Send response to client
     */
    public function send(): void;

    /**
     * Deny usage endpoint in iframe
     */
    public function denyIframe(): void;

    /**
     * Redirect on other page
     * @param string $url
     * @param boolean $temperatory Is 302 Moved Temperatory
     */
    public function location(string $url, bool $temperatory = false): void;

    /**
     * @return array
     */
    public function getHeaders(): array;

    /**
     * @return array
     */
    public function getCookies(): array;

    /**
     * @return string
     */
    public function getContent(): string;
}
