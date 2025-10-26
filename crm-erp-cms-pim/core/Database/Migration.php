<?php

namespace Core\Database;

/**
 * Base Migration Class
 * 
 * All migrations should extend this class
 */
abstract class Migration
{
    /**
     * Run the migration
     */
    abstract public function up(): void;
    
    /**
     * Reverse the migration
     */
    abstract public function down(): void;
    
    /**
     * Create a new table
     */
    protected function createTable(string $table, callable $callback): void
    {
        $schema = new Schema($table);
        $callback($schema);
        $schema->create();
    }
    
    /**
     * Drop a table
     */
    protected function dropTable(string $table): void
    {
        $sql = "DROP TABLE IF EXISTS `{$table}`";
        DB::connection()->statement($sql);
    }
    
    /**
     * Modify an existing table
     */
    protected function table(string $table, callable $callback): void
    {
        $schema = new Schema($table);
        $schema->setModifyMode(true);
        $callback($schema);
        $schema->alter();
    }
    
    /**
     * Rename a table
     */
    protected function renameTable(string $from, string $to): void
    {
        $sql = "RENAME TABLE `{$from}` TO `{$to}`";
        DB::connection()->statement($sql);
    }
    
    /**
     * Check if table exists
     */
    protected function hasTable(string $table): bool
    {
        $sql = "SHOW TABLES LIKE ?";
        $result = DB::connection()->selectOne($sql, [$table]);
        
        return $result !== null;
    }
}
