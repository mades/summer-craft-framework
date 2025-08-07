<?php

namespace SummerCraft\Core\Context;

class ApplicationContext
{
    public function __construct(
        private bool $isCli,
        private string $configLoader,
        /** Root project path */
        private string $basePath,
        /** Public Html */
        private string $publicPath,
        /** Generated entities: cache, logs, dumps, temps */
        private string $generatedDataPath,
        /** Pre-generated entities: language packages, icons, etc... */
        private string $resourcePath,
    ) {
    }

    public static function create(
        bool $isCli,
        string $configLoader,
        string $basePath,
        ?string $publicPath = null,
        ?string $generatedDataPath = null,
        ?string $resourcePath = null,
    ): self {
        return new self(
            isCli: $isCli,
            configLoader: $configLoader,
            basePath: $basePath,
            publicPath: $publicPath ?? $basePath . 'public_html/',
            generatedDataPath: $generatedDataPath ?? $basePath . 'storage/framework/',
            resourcePath: $resourcePath ?? $basePath . 'storage/resource/',
        );
    }

    public function isCLi(): bool
    {
        return $this->isCli;
    }

    public function getConfigLoader(): string
    {
        return $this->configLoader;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getPublicPath(): string
    {
        return $this->publicPath;
    }

    public function getTempPath(): string
    {
        return $this->generatedDataPath . 'temp/';
    }

    public function getCachePath(): string
    {
        return $this->generatedDataPath . 'cache/';
    }

    public function getLogsPath(): string
    {
        return $this->generatedDataPath . 'logs/';
    }

    public function getBackupPath(): string
    {
        return $this->generatedDataPath . 'backups/';
    }

    public function getLanguagePath(): string
    {
        return $this->resourcePath . 'language/';
    }

    public function getResourcePath(): string
    {
        return $this->resourcePath;
    }

}
