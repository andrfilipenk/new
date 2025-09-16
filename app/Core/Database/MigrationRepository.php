<?php
// app/Core/Database/MigrationRepository.php
namespace Core\Database;

use Core\Di\Injectable;

/**
 * Tracks migration status in the database
 */
class MigrationRepository
{
    use Injectable;

    protected $db;
    protected $table = 'migrations';

    public function __construct()
    {
        $this->db = $this->di->get(Database::class);
        $this->createMigrationsTable();
    }

    /**
     * Create migrations table if it doesn't exist
     */
    protected function createMigrationsTable()
    {
        $this->db->execute("
            CREATE TABLE IF NOT EXISTS {$this->table} (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    /**
     * Get all ran migrations
     */
    public function getRan()
    {
        $results = $this->db->table($this->table)
            ->select(['migration'])
            ->orderBy('batch', 'ASC')
            ->orderBy('migration', 'ASC')
            ->get();
            
        return array_map(function($item) {
            return $item->migration;
        }, $results);
    }

    /**
     * Get migrations by batch
     */
    public function getByBatch($batch)
    {
        $results = $this->db->table($this->table)
            ->where('batch', $batch)
            ->orderBy('migration', 'ASC')
            ->get();
            
        return array_map(function($item) {
            return $item->migration;
        }, $results);
    }

    /**
     * Get last batch number
     */
    public function getLastBatchNumber()
    {
        $result = $this->db->table($this->table)
            ->select([$this->db->raw('MAX(batch) as last_batch')])
            ->first();
            
        return $result ? $result->last_batch : 0;
    }

    /**
     * Log that a migration was run
     */
    public function log($migration, $batch)
    {
        $this->db->table($this->table)->insert([
            'migration' => $migration,
            'batch' => $batch
        ]);
    }

    /**
     * Remove a migration from the log
     */
    public function delete($migration)
    {
        $this->db->table($this->table)
            ->where('migration', $migration)
            ->delete();
    }
}