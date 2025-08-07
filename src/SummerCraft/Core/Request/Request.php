<?php

namespace SummerCraft\Core\Request;

interface Request
{
    public const METHOD_CLI = 1;
    public const METHOD_GET = 2;
    public const METHOD_POST = 3;
    public const METHOD_PUT = 4;
    public const METHOD_PATCH = 5;
    public const METHOD_DELETE = 6;
    public const METHOD_HEAD = 7;
    public const METHOD_CONNECT = 8;

    /**
     * Unique ID of request
     * @return int
     */
    public function getId(): int;

    /**
     * @return string[]
     */
    public function getSegments(): array;

    public function getSegmentsUri(): string;

    public function getProtocol(): string;

    public function getMethod(): int;

    public function getDomain(): string;

    public function getUri(): string;

    public function isCli(): bool;

    public function isAJAX(): bool;

    public function isSecure(): bool;

    public function getAllPostAsArray(): array;

    public function getAllGetAsArray(): array;

    public function getGetAsString(string $key, string $default = ''): string;

    public function getPostAsString(string $key, string $default = ''): string;

    public function getGetAsArray(string $key): array;

    public function getPostAsArray(string $key): array;

    public function getHead(string $key, string $default = ''): string;

    public function getFile(string $key): RequestFile;

    /**
     * @param string $key
     * @return RequestFile[]
     */
    public function getFiles(string $key): array;

    public function getRequestValue(string $key, string $default = ''): string;

    public function getCookie(string $key, string $default = ''): string;

    public function getClientIP(): string;
}
