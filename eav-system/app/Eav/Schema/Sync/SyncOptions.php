<?php

namespace App\Eav\Schema\Sync;

/**
 * Sync Options
 * 
 * Configuration options for schema synchronization.
 */
class SyncOptions
{
    // Sync strategies
    public const STRATEGY_ADDITIVE = 'additive';
    public const STRATEGY_FULL = 'full';
    public const STRATEGY_DRY_RUN = 'dry_run';

    private string $strategy;
    private bool $dryRun;
    private bool $autoBackup;
    private bool $force;
    private bool $skipValidation;

    public function __construct(
        string $strategy = self::STRATEGY_ADDITIVE,
        bool $dryRun = false,
        bool $autoBackup = true,
        bool $force = false,
        bool $skipValidation = false
    ) {
        $this->strategy = $strategy;
        $this->dryRun = $dryRun;
        $this->autoBackup = $autoBackup;
        $this->force = $force;
        $this->skipValidation = $skipValidation;
    }

    public function getStrategy(): string
    {
        return $this->strategy;
    }

    public function isDryRun(): bool
    {
        return $this->dryRun || $this->strategy === self::STRATEGY_DRY_RUN;
    }

    public function shouldAutoBackup(): bool
    {
        return $this->autoBackup && !$this->isDryRun();
    }

    public function isForce(): bool
    {
        return $this->force;
    }

    public function shouldSkipValidation(): bool
    {
        return $this->skipValidation;
    }

    public function isAdditive(): bool
    {
        return $this->strategy === self::STRATEGY_ADDITIVE;
    }

    public function isFull(): bool
    {
        return $this->strategy === self::STRATEGY_FULL;
    }
}
