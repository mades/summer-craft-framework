<?php

namespace SummerCraft\Core;

use ReflectionClass;
use ReflectionException;

class Autoloader
{
    private const CLASSES_CACHE_FILE = 'autoload/loadedClasses.php';
    private const CLASSES_CACHE_FILE_CLEAR_LOG = 'autoload/clearLog.php';

    private static string $basePath = '';
    private static string $cachePath = '';
    private static array $classPlaces = [];

    private array $loadedClasses = [];
    

    public static function create(
        string $basePath, 
        string $cachePath,
        array $classPlaces = []
    ): self {
        self::$basePath = $basePath;
        self::$cachePath = $cachePath;
        self::$classPlaces = $classPlaces;
        return new self();
    }

    private function __construct() {
    }

    public function setAutoloader(): void
    {
        spl_autoload_register(array($this, 'loadClass'), true, true);
    }

    public function loadClass(string $class): bool
    {
        $isFFF = strpos($class, 'HtmlExceptionResponseBuilder') !== false;

        if ($this->loadedClasses === null) {
            if (file_exists(self::$cachePath . self::CLASSES_CACHE_FILE)) {
                $this->loadedClasses = include self::$cachePath . self::CLASSES_CACHE_FILE;
            }
            if (!is_array($this->loadedClasses)) {
                $this->loadedClasses = [];
            }
        }
        //$this->loadedClasses['before_' . $class] = number_format(memory_get_usage(false) / 1024, 2);
        if (isset($this->loadedClasses[$class])) {
            include self::$basePath . $this->loadedClasses[$class];
            if (class_exists($class) || interface_exists($class)) {
                return true;
            }
            if (file_exists(self::$cachePath . self::CLASSES_CACHE_FILE)) {
                // Invalid cache file
                echo "INVALID_FILE[" . self::$cachePath . self::CLASSES_CACHE_FILE . "]";
                unlink(self::$cachePath . self::CLASSES_CACHE_FILE);
                file_put_contents(
                    self::$cachePath . self::CLASSES_CACHE_FILE_CLEAR_LOG,
                    "Invalid cache for class [$class] got[{$this->loadedClasses[$class]}]. File cleared" . ";\n",
                    FILE_APPEND | LOCK_EX
                );
                $this->loadedClasses = [];
            }
        }
        $file = self::getFileFromClassName($class);

        $success = false;
        if (!file_exists(self::$basePath . $file)) {
            $this->loadedClasses['UNKNOWN_' . $class] = $file;
        } else {
            include self::$basePath . $file;
            if (class_exists($class) || interface_exists($class)) {
                $this->loadedClasses[$class] = $file;
                $success = true;
            } else {
                $this->loadedClasses['INVALID_' . $class] = $file;
                $success = false;
            }
        }

        file_put_contents(
            self::$cachePath . self::CLASSES_CACHE_FILE,
            "<?php\n return " . var_export($this->loadedClasses, true) . ";\n",
            LOCK_EX
        );
        file_put_contents(
            self::$cachePath . self::CLASSES_CACHE_FILE_CLEAR_LOG,
            gmdate ('Y-m-d H:i:s') . ": Added $class class;\n",
            FILE_APPEND | LOCK_EX
        );
        return $success;
    }

    public function getLoadedClasses(): array
    {
       return $this->loadedClasses;
    }

    private static function getBasePath(): string
    {
        if (!empty(self::$basePath)) {
            return self::$basePath;
        } elseif (Application::hasInstance()) {
            return Application::getInstance()->getContext()->getBasePath();
        } else {
            throw new \RuntimeException("Can not get base path. Application not created");
        }
    }

    public static function getFullFileFromClassName(string $className): string
    {
        return self::getBasePath() . self::getFileFromClassName($className);
    }

    public static function getFileFromClassName(string $className): string
    {
        $explodedClass = explode('\\', $className);
        if (isset(self::$classPlaces[$explodedClass[0]])) {
            $explodedClass[0] = self::$classPlaces[$explodedClass[0]];
            return implode(DIRECTORY_SEPARATOR, $explodedClass) . '.php';
        }

        foreach (self::$classPlaces as $classStartsWith => $classDestination) {
            if (str_starts_with($className, $classStartsWith)) {
                $resultFile = str_replace($classStartsWith, $classDestination, $className);
                return str_replace('\\', DIRECTORY_SEPARATOR, $resultFile) . 'php';
            }
        }

        try {
            $reflectionClass = new ReflectionClass($className);
            return $reflectionClass->getFileName();
        } catch (ReflectionException $e) {
        }

        return implode(DIRECTORY_SEPARATOR, $explodedClass) . '.php';
    }
}
