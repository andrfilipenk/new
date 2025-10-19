<?php

namespace App\Eav\Config;

/**
 * Schema Management Configuration
 * 
 * Centralized configuration for EAV schema management system.
 */
class SchemaConfig
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaults(), $config);
    }

    /**
     * Get default configuration
     */
    private function getDefaults(): array
    {
        return [
            // Auto-sync configuration
            'auto_sync' => false,
            
            // Backup configuration
            'backup_before_sync' => true,
            'backup_storage_path' => __DIR__ . '/../../../storage/eav/backups',
            'max_backup_retention_days' => 30,
            'backup_compression' => false,
            
            // Sync configuration
            'default_strategy' => 'additive', // additive|full
            'allow_destructive_migrations' => false,
            'skip_validation' => false,
            
            // Migration configuration
            'migration_path' => __DIR__ . '/../../../migrations',
            'migration_namespace' => '',
            'migration_template' => 'default',
            
            // Cache configuration
            'cache_enabled' => true,
            'schema_cache_ttl' => 300, // 5 minutes
            
            // Analysis configuration
            'analysis_log_enabled' => true,
            'conflict_detection_enabled' => true,
            
            // Performance configuration
            'query_timeout' => 30,
            'batch_size' => 1000,
        ];
    }

    /**
     * Get configuration value
     */
    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set configuration value
     */
    public function set(string $key, $value): void
    {
        $this->config[$key] = $value;
    }

    /**
     * Get all configuration
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Check if auto-sync is enabled
     */
    public function isAutoSyncEnabled(): bool
    {
        return (bool) $this->get('auto_sync');
    }

    /**
     * Check if backup before sync is enabled
     */
    public function shouldBackupBeforeSync(): bool
    {
        return (bool) $this->get('backup_before_sync');
    }

    /**
     * Get backup storage path
     */
    public function getBackupStoragePath(): string
    {
        return (string) $this->get('backup_storage_path');
    }

    /**
     * Get migration path
     */
    public function getMigrationPath(): string
    {
        return (string) $this->get('migration_path');
    }

    /**
     * Get default sync strategy
     */
    public function getDefaultStrategy(): string
    {
        return (string) $this->get('default_strategy');
    }

    /**
     * Check if destructive migrations are allowed
     */
    public function allowDestructiveMigrations(): bool
    {
        return (bool) $this->get('allow_destructive_migrations');
    }

    /**
     * Get schema cache TTL
     */
    public function getSchemaCacheTtl(): int
    {
        return (int) $this->get('schema_cache_ttl');
    }

    /**
     * Check if cache is enabled
     */
    public function isCacheEnabled(): bool
    {
        return (bool) $this->get('cache_enabled');
    }

    /**
     * Get max backup retention in days
     */
    public function getMaxBackupRetentionDays(): int
    {
        return (int) $this->get('max_backup_retention_days');
    }

    /**
     * Check if analysis logging is enabled
     */
    public function isAnalysisLogEnabled(): bool
    {
        return (bool) $this->get('analysis_log_enabled');
    }

    /**
     * Load configuration from array
     */
    public static function fromArray(array $config): self
    {
        return new self($config);
    }

    /**
     * Load configuration from file
     */
    public static function fromFile(string $filePath): self
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Configuration file not found: $filePath");
        }

        $config = require $filePath;
        
        if (!is_array($config)) {
            throw new \RuntimeException("Configuration file must return an array");
        }

        return new self($config['eav']['schema'] ?? []);
    }
}
