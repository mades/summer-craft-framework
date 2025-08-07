<?php

namespace SummerCraft\Core\Exception;

use Exception;

class PhpErrorException extends Exception
{
    /** @var mixed $severity */
    protected $severity;

    protected string $errorFile;

    protected int $errorLine;

    /**
     * @param mixed $severity
     */
    public function setSeverity($severity)
    {
        $this->severity = $severity;
    }

    public function setErrorFile(string $errorFile)
    {
        $this->errorFile = $errorFile;
    }

    /**
     * @param int $errorLine
     */
    public function setErrorLine($errorLine)
    {
        $this->errorLine = $errorLine;
    }

    public function getSeverity()
    {
        return $this->severity;
    }

    public function getErrorFile()
    {
        return $this->errorFile;
    }

    public function getErrorLine()
    {
        return $this->errorLine;
    }
}
