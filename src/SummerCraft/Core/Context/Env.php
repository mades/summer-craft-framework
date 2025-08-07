<?php

namespace SummerCraft\Core\Context;

use RuntimeException;

class Env
{
    public static function loadEnvFromIni(string $iniFilePath): void
    {
        $vars = parse_ini_file($iniFilePath);
        $_ENV = array_merge($vars, $_ENV);
    }

    public static function getString(string $key, ?string $default = null): string
    {
        if (!array_key_exists($key, $_ENV)) {
            if ($default === null) {
                throw new RuntimeException("Key $key not found in env variables");
            }
            return $default;
        }
        return (string)$_ENV[$key];
    }
}