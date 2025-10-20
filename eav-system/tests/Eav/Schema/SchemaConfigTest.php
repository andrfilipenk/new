<?php

namespace Tests\Eav\Schema;

use PHPUnit\Framework\TestCase;
use App\Eav\Schema\Config\SchemaConfig;

/**
 * Unit Tests for SchemaConfig
 */
class SchemaConfigTest extends TestCase
{
    public function testDefaultConfiguration(): void
    {
        $config = new SchemaConfig();

        $this->assertFalse($config->isAutoSyncEnabled());
        $this->assertTrue($config->shouldBackupBeforeSync());
        $this->assertEquals('additive', $config->getDefaultStrategy());
        $this->assertFalse($config->allowDestructiveMigrations());
        $this->assertTrue($config->isCacheEnabled());
    }

    public function testCustomConfiguration(): void
    {
        $config = new SchemaConfig([
            'auto_sync' => true,
            'default_strategy' => 'full',
            'allow_destructive_migrations' => true,
        ]);

        $this->assertTrue($config->isAutoSyncEnabled());
        $this->assertEquals('full', $config->getDefaultStrategy());
        $this->assertTrue($config->allowDestructiveMigrations());
    }

    public function testGetAndSet(): void
    {
        $config = new SchemaConfig();

        $config->set('custom_key', 'custom_value');
        $this->assertEquals('custom_value', $config->get('custom_key'));
    }

    public function testGetWithDefault(): void
    {
        $config = new SchemaConfig();

        $this->assertEquals('default_value', $config->get('non_existent_key', 'default_value'));
    }

    public function testCacheTtl(): void
    {
        $config = new SchemaConfig(['schema_cache_ttl' => 600]);

        $this->assertEquals(600, $config->getSchemaCacheTtl());
    }

    public function testBackupRetention(): void
    {
        $config = new SchemaConfig(['max_backup_retention_days' => 60]);

        $this->assertEquals(60, $config->getMaxBackupRetentionDays());
    }

    public function testFromArray(): void
    {
        $config = SchemaConfig::fromArray([
            'auto_sync' => true,
            'cache_enabled' => false,
        ]);

        $this->assertTrue($config->isAutoSyncEnabled());
        $this->assertFalse($config->isCacheEnabled());
    }
}
