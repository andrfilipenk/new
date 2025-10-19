<?php

namespace Tests\Eav\Schema;

use PHPUnit\Framework\TestCase;
use App\Eav\Schema\Sync\SyncOptions;

/**
 * Unit Tests for SyncOptions
 */
class SyncOptionsTest extends TestCase
{
    public function testDefaultOptions(): void
    {
        $options = new SyncOptions();

        $this->assertEquals(SyncOptions::STRATEGY_ADDITIVE, $options->getStrategy());
        $this->assertFalse($options->isDryRun());
        $this->assertTrue($options->shouldAutoBackup());
        $this->assertFalse($options->isForce());
        $this->assertFalse($options->shouldSkipValidation());
    }

    public function testAdditiveStrategy(): void
    {
        $options = new SyncOptions(strategy: SyncOptions::STRATEGY_ADDITIVE);

        $this->assertTrue($options->isAdditive());
        $this->assertFalse($options->isFull());
    }

    public function testFullStrategy(): void
    {
        $options = new SyncOptions(strategy: SyncOptions::STRATEGY_FULL);

        $this->assertFalse($options->isAdditive());
        $this->assertTrue($options->isFull());
    }

    public function testDryRunMode(): void
    {
        $options = new SyncOptions(dryRun: true);

        $this->assertTrue($options->isDryRun());
        $this->assertFalse($options->shouldAutoBackup()); // No backup in dry run
    }

    public function testDryRunStrategyOverride(): void
    {
        $options = new SyncOptions(strategy: SyncOptions::STRATEGY_DRY_RUN);

        $this->assertTrue($options->isDryRun());
    }

    public function testForceMode(): void
    {
        $options = new SyncOptions(force: true);

        $this->assertTrue($options->isForce());
    }

    public function testAutoBackupDisabled(): void
    {
        $options = new SyncOptions(autoBackup: false);

        $this->assertFalse($options->shouldAutoBackup());
    }

    public function testSkipValidation(): void
    {
        $options = new SyncOptions(skipValidation: true);

        $this->assertTrue($options->shouldSkipValidation());
    }
}
