<?php

namespace Core\Database;

/**
 * Migrator
 * 
 * Manages database migrations
 */
class Migrator
{
    protected string $migrationsPath;
    protected array $ran = [];
    
    public function __construct($migrationsPath = null)
    {
        $this->migrationsPath = $migrationsPath ?? dirname(__DIR__, 2) . '/database/migrations';
        $this->ensureMigrationsTableExists();
        $this->loadRanMigrations();
    }
    
    /**
     * Ensure migrations table exists
     */
    protected function ensureMigrationsTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) NOT NULL,
            `batch` INT NOT NULL,
            `ran_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        DB::connection()->statement($sql);
    }
    
    /**
     * Load ran migrations from database
     */
    protected function loadRanMigrations(): void
    {
        $result = DB::table('migrations')
            ->select('migration')
            ->orderBy('batch')
            ->orderBy('id')
            ->get();
        
        $this->ran = array_column($result, 'migration');
    }
    
    /**
     * Run pending migrations
     */
    public function run(): array
    {
        $files = $this->getMigrationFiles();
        $pending = array_diff($files, $this->ran);
        
        if (empty($pending)) {
            return [];
        }
        
        $batch = $this->getNextBatchNumber();
        $ranMigrations = [];
        
        foreach ($pending as $migration) {
            $this->runMigration($migration, $batch);
            $ranMigrations[] = $migration;
        }
        
        return $ranMigrations;
    }
    
    /**
     * Rollback last batch of migrations
     */
    public function rollback(int $steps = 1): array
    {
        $batches = $this->getBatches();
        
        if (empty($batches)) {
            return [];
        }
        
        $rolledBack = [];
        
        for ($i = 0; $i < $steps; $i++) {
            if (empty($batches)) {
                break;
            }
            
            $batch = array_pop($batches);
            $migrations = $this->getMigrationsInBatch($batch);
            
            foreach (array_reverse($migrations) as $migration) {
                $this->rollbackMigration($migration);
                $rolledBack[] = $migration;
            }
        }
        
        return $rolledBack;
    }
    
    /**
     * Reset all migrations
     */
    public function reset(): array
    {
        $migrations = array_reverse($this->ran);
        $rolledBack = [];
        
        foreach ($migrations as $migration) {
            $this->rollbackMigration($migration);
            $rolledBack[] = $migration;
        }
        
        return $rolledBack;
    }
    
    /**
     * Refresh all migrations
     */
    public function refresh(): array
    {
        $this->reset();
        return $this->run();
    }
    
    /**
     * Run a single migration
     */
    protected function runMigration(string $migration, int $batch): void
    {
        $instance = $this->resolve($migration);
        
        DB::beginTransaction();
        
        try {
            $instance->up();
            
            // Record in migrations table
            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => $batch,
            ]);
            
            DB::commit();
            
            $this->ran[] = $migration;
        } catch (\Exception $e) {
            if (DB::inTransaction()) {
                DB::rollBack();
            }
            throw new \Exception("Migration failed: {$migration}\n{$e->getMessage()}");
        }
    }
    
    /**
     * Rollback a single migration
     */
    protected function rollbackMigration(string $migration): void
    {
        $instance = $this->resolve($migration);
        
        DB::beginTransaction();
        
        try {
            $instance->down();
            
            // Remove from migrations table
            DB::table('migrations')
                ->where('migration', '=', $migration)
                ->delete();
            
            DB::commit();
            
            // Remove from ran array
            $this->ran = array_diff($this->ran, [$migration]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception("Rollback failed: {$migration}\n{$e->getMessage()}");
        }
    }
    
    /**
     * Resolve migration instance
     */
    protected function resolve(string $migration): Migration
    {
        $file = $this->migrationsPath . '/' . $migration . '.php';
        
        if (!file_exists($file)) {
            throw new \Exception("Migration file not found: {$file}");
        }
        
        require_once $file;
        
        // Extract class name from migration filename
        // Format: YYYY_MM_DD_HHMMSS_create_users_table.php
        $parts = explode('_', $migration);
        $className = '';
        
        // Skip timestamp parts (first 5 elements: YYYY, MM, DD, HH, MMSS)
        for ($i = 4; $i < count($parts); $i++) {
            $className .= ucfirst($parts[$i]);
        }
        
        if (!class_exists($className)) {
            throw new \Exception("Migration class not found: {$className} in file {$file}");
        }
        
        return new $className;
    }
    
    /**
     * Get all migration files
     */
    protected function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            return [];
        }
        
        $files = scandir($this->migrationsPath);
        $migrations = [];
        
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $migrations[] = pathinfo($file, PATHINFO_FILENAME);
            }
        }
        
        sort($migrations);
        
        return $migrations;
    }
    
    /**
     * Get next batch number
     */
    protected function getNextBatchNumber(): int
    {
        $result = DB::table('migrations')
            ->select('batch')
            ->orderBy('batch', 'desc')
            ->first();
        
        return ($result['batch'] ?? 0) + 1;
    }
    
    /**
     * Get all batch numbers
     */
    protected function getBatches(): array
    {
        $result = DB::table('migrations')
            ->select('batch')
            ->groupBy('batch')
            ->orderBy('batch')
            ->get();
        
        return array_column($result, 'batch');
    }
    
    /**
     * Get migrations in a batch
     */
    protected function getMigrationsInBatch(int $batch): array
    {
        $result = DB::table('migrations')
            ->where('batch', '=', $batch)
            ->orderBy('id')
            ->get();
        
        return array_column($result, 'migration');
    }
    
    /**
     * Get pending migrations
     */
    public function getPending(): array
    {
        $files = $this->getMigrationFiles();
        return array_diff($files, $this->ran);
    }
    
    /**
     * Get ran migrations
     */
    public function getRan(): array
    {
        return $this->ran;
    }
}
