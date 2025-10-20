<?php

namespace App\Eav\Schema\Migration;

use Core\Database\Connection;

/**
 * Migration Executor
 */
class MigrationExecutor
{
    private Connection $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * Execute migration
     */
    public function execute(Migration $migration, string $direction = 'up'): void
    {
        require_once $migration->getFilePath();
        
        $className = $this->getClassNameFromFile($migration->getFilePath());
        $instance = new $className($this->db);
        
        if ($direction === 'up') {
            $instance->up();
        } else {
            $instance->down();
        }
    }

    /**
     * Get class name from migration file
     */
    private function getClassNameFromFile(string $filePath): string
    {
        $content = file_get_contents($filePath);
        preg_match('/class\s+(\w+)/', $content, $matches);
        return $matches[1] ?? '';
    }
}
