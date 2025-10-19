<?php

namespace App\Eav\Schema\Migration;

/**
 * Migration
 */
class Migration
{
    private string $name;
    private string $filePath;
    private string $code;
    private string $entityTypeCode;
    private bool $created = false;

    public function __construct(
        string $name,
        string $filePath,
        string $code,
        string $entityTypeCode
    ) {
        $this->name = $name;
        $this->filePath = $filePath;
        $this->code = $code;
        $this->entityTypeCode = $entityTypeCode;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getEntityTypeCode(): string
    {
        return $this->entityTypeCode;
    }

    public function isCreated(): bool
    {
        return $this->created;
    }

    public function setCreated(bool $created): void
    {
        $this->created = $created;
    }
}
