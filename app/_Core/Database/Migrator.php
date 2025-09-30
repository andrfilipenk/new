<?php
// app/_Core/Database/Migrator.php
namespace Core\Database;

use Core\Di\Injectable;

/**
 * Handles migration execution
 */
class Migrator
{
    use Injectable;

    /**
     * Holds repository instance
     *
     * @var MigrationRepository
     */
    protected $repository;

    /**
     * Holds path to migrations
     *
     * @var string
     */
    protected $migrationsPath;

    public function __construct()
    {
        $this->repository = $this->getDI()->get('migrationRepository');
        $config = $this->getDI()->get('config');
        $this->migrationsPath = $config['migrations']['path'];
    }

    /**
     * Run all outstanding migrations
     */
    public function run()
    {
        $ran        = $this->repository->getRan();
        $files      = $this->getMigrationFiles();
        $pending    = array_diff($files, $ran);
        if (empty($pending)) {
            return [];
        }
        $batch      = $this->repository->getLastBatchNumber() + 1;
        $executed   = [];
        foreach ($pending as $migration) {
            $this->runMigration($migration, $batch);
            $executed[] = $migration;
            usleep(50000);
        }
        return $executed;
    }

    /**
     * Rollback the last migration batch
     */
    public function rollback()
    {
        $batch      = $this->repository->getLastBatchNumber();
        $migrations = $this->repository->getByBatch($batch);
        if (empty($migrations)) {
            return [];
        }
        $rolledBack = [];
        foreach (array_reverse($migrations) as $migration) {
            $this->rollbackMigration($migration);
            $rolledBack[] = $migration;
        }
        return $rolledBack;
    }

    /**
     * Rollback all migrations
     */
    public function reset()
    {
        $batches    = $this->repository->getRan();
        $rolledBack = [];
        while (!empty($batches)) {
            $rolledBack = array_merge($rolledBack, $this->rollback());
            $batches    = $this->repository->getRan();
        }
        return $rolledBack;
    }

    /**
     * Get all migration files
     */
    protected function getMigrationFiles()
    {
        $files = scandir($this->migrationsPath);
        return array_filter($files, function($file) {
            return preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_.+\.php$/', $file);
        });
    }

    /**
     * Run a specific migration
     */
    protected function runMigration($migration, $batch)
    {
        require_once $this->migrationsPath . '/' . $migration;
        $className  = $this->getMigrationClassName($migration);
        $instance   = new $className();
        $instance->up();
        $this->repository->log($migration, $batch);
    }

    /**
     * Rollback a specific migration
     */
    protected function rollbackMigration($migration)
    {
        require_once $this->migrationsPath . '/' . $migration;
        $className  = $this->getMigrationClassName($migration);
        $instance   = new $className();
        $instance->down();
        $this->repository->delete($migration);
    }

    /**
     * Extract class name from migration file
     */
    protected function getMigrationClassName($migration)
    {
        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $migration);
        $name = preg_replace('/\.php$/', '', $name);
        $name = str_replace('_', ' ', $name);
        $name = ucwords($name);
        $name = str_replace(' ', '', $name);
        return $name;
    }
}