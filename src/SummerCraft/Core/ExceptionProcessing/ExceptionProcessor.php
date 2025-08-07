<?php

namespace SummerCraft\Core\ExceptionProcessing;

use Exception;
use Psr\Log\LoggerInterface;
use SummerCraft\Core\Application;
use SummerCraft\Core\ComponentManaging\RequestScope;
use SummerCraft\Core\Context\ApplicationContext;
use SummerCraft\Core\Response\Response;
use SummerCraft\Core\Exception\PhpErrorException;
use Throwable;

/**
 * Class for Fatal framework errors
 */
class ExceptionProcessor
{
    public const BACKTRACE_TYPE_DEFAULT_SPECIFIC = 1;
    public const BACKTRACE_TYPE_DEFAULT_WITH_PARAMETER = 2;

    public static function configureDefaultHandlers(): void
    {
        set_error_handler(function (int $severity, string $message, string $filePath = '', int $line = 0): void {
            ExceptionProcessor::defaultProcessError(
                $severity,
                $message,
                $filePath,
                $line
            );
        });
        set_exception_handler(function ($exception): void {
            ExceptionProcessor::defaultProcessException(
                $exception,
                null
            );
        });
        register_shutdown_function(function (): void {
            $last_error = error_get_last();
            if (isset($last_error)
                && ($last_error['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_COMPILE_WARNING))
            ) {
                ExceptionProcessor::defaultProcessError(
                    $last_error['type'],
                    $last_error['message'],
                    $last_error['file'],
                    $last_error['line']
                );
            }
        });
    }

    public static function defaultProcessException(Throwable $exception, ?RequestScope $requestScope): void
    {
        $result = self::defaultProcessExceptionToString($exception, $requestScope);
        echo $result;
    }

    public static function defaultProcessExceptionToString(Throwable $exception, ?RequestScope $requestScope): string
    {
        $isCli = true;
        $debugBacktraceType = ExceptionProcessor::BACKTRACE_TYPE_DEFAULT_SPECIFIC;
        /** @var Exception[] $exceptions */
        $exceptions = [$exception];
        $showErrors = true;
        try {
            $exceptionDescription = new ExceptionDescription($exception);
            $app = Application::getInstance();
            $appContext = $app->getContext();
            $isCli = $appContext->isCli();
            $config = $app->get(ExceptionProcessingConfig::class);

            $showErrors = self::isShowErrors($appContext, $config);
            $debugBacktraceType = $config->debugBacktraceType;

            $logger = $app->get(LoggerInterface::class);
            $logger->log(
                $exceptionDescription->logLevel(),
                "{$exceptionDescription->getMessage()} on {$exceptionDescription->getFileName()}:{$exceptionDescription->getFileLine()}",
                [
                    'trace' => $exception->getTraceAsString(),
                    'server' => $_SERVER,
                    'tag' => 'exceptions',
                ],
            );

            if ($requestScope === null) {
                if (!$isCli && $exceptionDescription->isError()) {
                    header('HTTP/1.1' . ' ' . 500 . ' ' . Response::HTTP_CODES[500], TRUE, 500);
                }
            }

        } catch (Throwable $exceptionInner) {
            $exceptions[] = $exceptionInner;
        }
        if (!$showErrors) {
            return '';
        }

        $exceptions = array_merge($exceptions, self::extractExceptions($exception));
        $exceptionDescriptions = [];
        foreach ($exceptions as $exception) {
            $exceptionDescriptions[] = new ExceptionDescription($exception);
        }

        return $isCli
            ? CliExceptionResponseBuilder::buildForException($exceptionDescriptions, $debugBacktraceType)
            : HtmlExceptionResponseBuilder::buildForException($exceptionDescriptions, $debugBacktraceType);
    }

    public static function defaultProcessError(
        int $severity,
        string $message,
        string $filepath,
        int $line
    ): void {
        $phpException = new PhpErrorException($message);
        $phpException->setSeverity($severity);
        $phpException->setErrorFile($filepath);
        $phpException->setErrorLine($line);
        self::defaultProcessException($phpException, null);
    }

    public static function initErrorConfiguration(
        ApplicationContext $applicationContext,
        ExceptionProcessingConfig $config
    ): void {
        if (self::isShowErrors($applicationContext, $config)) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', 0);
        }
    }

    private static function isShowErrors(
        ApplicationContext $applicationContext,
        ExceptionProcessingConfig $config
    ): bool {
        return $applicationContext->isCLi() ? $config->showCliErrors : $config->showErrors;
    }

    /**
     * Result exception and previous exception as array
     * @param Throwable $exception
     * @return Throwable[]
     */
    public static function extractExceptions(Throwable $exception) {
        $result = [];
        //$result[] = $exception;
        $previous = $exception->getPrevious();
        if ($previous) {
            $previousResult = self::extractExceptions($previous);
            $result = array_merge($result, $previousResult);
        }
        return $result;
    }
}
