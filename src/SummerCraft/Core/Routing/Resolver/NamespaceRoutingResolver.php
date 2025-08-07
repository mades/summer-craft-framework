<?php

namespace SummerCraft\Core\Routing\Resolver;

use RuntimeException;
use SummerCraft\Core\Routing\RoutingEntryPoint;

/**
 * Auto-found entry point by path like:
 * /some1/some2/some3/some4/some5 -> \Some1\Some2Controller::some3Action(some4, some5)
 */
class NamespaceRoutingResolver implements RoutingResolver
{
    private const CONTROLLER_POSTFIX = 'Controller';
    private const CONTROLLER_SNAKE_POSTFIX = '_controller';
    private const METHOD_POSTFIX = 'Action';
    private const METHOD_SNAKE_POSTFIX = '_action';
    private const DEFAULT_CONTROLLER_NAME = 'Default';
    private const DEFAULT_METHOD_NAME = 'default';

    private string $namespaceRootClassName;

    /**
     * @var string[]
     */
    private array $replaceParts;

    private string $methodPostfix = self::METHOD_POSTFIX;

    private string $controllerPostfix = self::CONTROLLER_POSTFIX;

    public function __construct(
        string $namespaceRootClassName,
        array $translateSegments = []
    ){
        $this->namespaceRootClassName = $namespaceRootClassName;
        $this->replaceParts = $translateSegments;
    }

    public static function camelBased(string $rootClassName): self
    {
        return new self($rootClassName);
    }

    public static function snakeBased(string $rootClassName): self
    {
        $resolver = new self($rootClassName);
        $resolver->controllerPostfix = self::CONTROLLER_SNAKE_POSTFIX;
        $resolver->methodPostfix = self::METHOD_SNAKE_POSTFIX;
        return $resolver;
    }

    public function withReplacePairs(array $replacePairs): self
    {
        $this->replaceParts = array_merge($this->replaceParts, $replacePairs);
        return $this;
    }

    public function getRoutingEntryPoint(array $uriMatchData): ?RoutingEntryPoint
    {
        $currentNamespace = self::rootFileNamespace($this->namespaceRootClassName);

        $rootFile = self::realPathOfClass($this->namespaceRootClassName);
        if ($rootFile === null) {
            throw new RuntimeException("Class location of {$this->namespaceRootClassName} not found");
        }
        $currentDirectory = dirname($rootFile);

        $segments = explode('/', $uriMatchData[1] ?? '');
        if ($segments[0] === '') {
            array_shift($segments);
        }

        // *** Shift from segments directories ***
        $segmentsCount = count($segments);
        for ($i = 0; $i < $segmentsCount; $i++) {

            $segmentTest = ucfirst($this->replaceSettledPairs($segments[0]));
            $nameTest = $currentDirectory . $segmentTest;
            if (!is_dir($nameTest)) {
                break;
            }

            $currentNamespace .= '\\' . $segmentTest;
            $currentDirectory .= $segmentTest . '/';
            array_shift($segments);
        }

        $testClassName = $currentNamespace . '\\' . self::DEFAULT_CONTROLLER_NAME . $this->controllerPostfix;
        if (!empty($segments)) {
            $testClassName = $currentNamespace . '\\'
                . ucfirst($this->replaceSettledPairs($segments[0])) . $this->controllerPostfix;
            array_shift($segments);
        }

        $testMethodName = self::DEFAULT_METHOD_NAME . $this->methodPostfix;
        if (!empty($segments)) {
            $testMethodName = ucfirst($this->replaceSettledPairs($segments[0])) . $this->methodPostfix;
            array_shift($segments);
        }

        if (!class_exists($testClassName,true)) {
            //Debugger::log(['class not exist', $testClassName]);
            return null;
        }
        if (!method_exists($testClassName, $testMethodName)) {
            //Debugger::log(['method not exist', $testClassName, $testMethodName]);
            return null;
        }

        return new RoutingEntryPoint(
            $testClassName,
            $testMethodName,
            $segments
        );
    }

    private function replaceSettledPairs(string $segment): string
    {
        foreach ($this->replaceParts as $replacePartKey => $replacePartValue) {
            $segment = str_replace($replacePartKey, $replacePartValue, $segment);
        }
        return str_replace('.', '', $segment);
    }

    private static function rootFileNamespace(string $rootClassName): string
    {
        $rootClassNameSegments = explode('\\', $rootClassName);
        unset($rootClassNameSegments[count($rootClassNameSegments) - 1]);
        return '\\' . implode('\\', $rootClassNameSegments );
    }

    public static function realPathOfClass(string $className): ?string
    {
        try {
            return (new \ReflectionClass($className))->getFileName();
        } catch (\Exception $exception) {
            return null;
        }
    }
}
