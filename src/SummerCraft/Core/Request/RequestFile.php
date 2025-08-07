<?php

namespace SummerCraft\Core\Request;

class RequestFile
{
    private string $tmpFile;

    private string $name;

    private string $error;

    public function __construct(
        string $tmpFile,
        string $name,
        string $error
    ) {
        $this->tmpFile = $tmpFile;
        $this->name = $name;
        $this->error = $error;
    }

    public function getTmpFile(): string
    {
        return $this->tmpFile;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getError(): string
    {
        return $this->error;
    }
}
