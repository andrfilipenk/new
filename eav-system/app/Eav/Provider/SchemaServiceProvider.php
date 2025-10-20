<?php

namespace App\Eav\Provider;

use App\Eav\Schema\Analysis\SchemaAnalyzer;
use App\Eav\Schema\Comparison\SchemaComparator;
use App\Eav\Schema\Sync\SynchronizationEngine;
use App\Eav\Schema\Migration\MigrationGenerator;
use App\Eav\Schema\Migration\MigrationValidator;
use App\Eav\Schema\Migration\MigrationExecutor;
use App\Eav\Schema\Backup\BackupManager;
use App\Eav\Config\EntityTypeRegistry;
use App\Eav\Config\SchemaConfig;
use Core\Di\ServiceProvider;
use Core\Di\Container;
use Core\Database\Connection;
use Core\Events\EventDispatcher;

/**
 * Schema Service Provider
 * 
 * Registers schema management services in the DI container.
 */
class SchemaServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(Container $container): void
    {
        // Register SchemaConfig
        $container->singleton('schemaConfig', function($c) {
            $configPath = __DIR__ . '/../../../config.php';
            
            if (file_exists($configPath)) {
                return SchemaConfig::fromFile($configPath);
            }
            
            return new SchemaConfig();
        });

        // Register SchemaAnalyzer
        $container->singleton('schemaAnalyzer', function($c) {
            return new SchemaAnalyzer(
                $c->get('db'),
                $c->get('eavEntityTypeRegistry')
            );
        });

        // Register SchemaComparator
        $container->singleton('schemaComparator', function($c) {
            return new SchemaComparator();
        });

        // Register MigrationGenerator
        $container->singleton('migrationGenerator', function($c) {
            $config = $c->get('schemaConfig');
            return new MigrationGenerator($config->getMigrationPath());
        });

        // Register MigrationValidator
        $container->singleton('migrationValidator', function($c) {
            return new MigrationValidator();
        });

        // Register MigrationExecutor
        $container->singleton('migrationExecutor', function($c) {
            return new MigrationExecutor($c->get('db'));
        });

        // Register BackupManager
        $container->singleton('backupManager', function($c) {
            $config = $c->get('schemaConfig');
            return new BackupManager(
                $c->get('db'),
                $c->get('eavEntityTypeRegistry'),
                $config->getBackupStoragePath()
            );
        });

        // Register SynchronizationEngine
        $container->singleton('synchronizationEngine', function($c) {
            return new SynchronizationEngine(
                $c->get('schemaAnalyzer'),
                $c->get('schemaComparator'),
                $c->get('migrationGenerator'),
                $c->get('migrationExecutor'),
                $c->get('backupManager'),
                $c->get('eavEntityTypeRegistry'),
                $c->get('db'),
                $c->has('eventDispatcher') ? $c->get('eventDispatcher') : null
            );
        });

        // Register CLI Commands
        $this->registerCommands($container);
    }

    /**
     * Register CLI commands
     */
    private function registerCommands(Container $container): void
    {
        $container->singleton('command.schema.analyze', function($c) {
            return new \App\Eav\Console\SchemaAnalyzeCommand(
                $c->get('schemaAnalyzer')
            );
        });

        $container->singleton('command.schema.sync', function($c) {
            return new \App\Eav\Console\SchemaSyncCommand(
                $c->get('synchronizationEngine')
            );
        });

        $container->singleton('command.backup.create', function($c) {
            return new \App\Eav\Console\BackupCreateCommand(
                $c->get('backupManager')
            );
        });

        $container->singleton('command.backup.list', function($c) {
            return new \App\Eav\Console\BackupListCommand(
                $c->get('backupManager')
            );
        });
    }

    /**
     * Boot services
     */
    public function boot(Container $container): void
    {
        // Register event listeners if event dispatcher is available
        if ($container->has('eventDispatcher')) {
            $this->registerEventListeners($container);
        }

        // Setup auto-sync if enabled
        $config = $container->get('schemaConfig');
        if ($config->isAutoSyncEnabled()) {
            $this->setupAutoSync($container);
        }
    }

    /**
     * Register event listeners
     */
    private function registerEventListeners(Container $container): void
    {
        $eventDispatcher = $container->get('eventDispatcher');

        // Listen for schema sync events
        $eventDispatcher->listen('schema.sync.started', function($event) {
            // Log sync start
        });

        $eventDispatcher->listen('schema.sync.completed', function($event) {
            // Log sync completion
        });

        $eventDispatcher->listen('schema.sync.failed', function($event) {
            // Log sync failure
        });
    }

    /**
     * Setup auto-sync functionality
     */
    private function setupAutoSync(Container $container): void
    {
        // Auto-sync would be implemented here
        // This could watch for config changes and trigger sync automatically
    }

    /**
     * Get provided services
     */
    public function provides(): array
    {
        return [
            'schemaConfig',
            'schemaAnalyzer',
            'schemaComparator',
            'migrationGenerator',
            'migrationValidator',
            'migrationExecutor',
            'backupManager',
            'synchronizationEngine',
            'command.schema.analyze',
            'command.schema.sync',
            'command.backup.create',
            'command.backup.list',
        ];
    }
}
