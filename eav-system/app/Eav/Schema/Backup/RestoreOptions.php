<?php

namespace App\Eav\Schema\Backup;

/**
 * Restore Options
 */
class RestoreOptions
{
    private ?string $targetEntityType;
    private bool $verifyOnly;
    private bool $force;

    public function __construct(
        ?string $targetEntityType = null,
        bool $verifyOnly = false,
        bool $force = false
    ) {
        $this->targetEntityType = $targetEntityType;
        $this->verifyOnly = $verifyOnly;
        $this->force = $force;
    }

    public function getTargetEntityType(): ?string
    {
        return $this->targetEntityType;
    }

    public function isVerifyOnly(): bool
    {
        return $this->verifyOnly;
    }

    public function isForce(): bool
    {
        return $this->force;
    }
}
