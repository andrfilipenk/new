<?php
namespace Core\Database;

use Core\Di\Injectable;

/**
 * Base Migration class
 */
abstract class Migration
{
    use Injectable;

    /** @var Database $db */
    protected $db;

    public function __construct()
    {
        $this->db = $this->getDI()->get('db');
    }

    /**
     * Run the migration
     */
    abstract public function up();

    /**
     * Reverse the migration
     */
    abstract public function down();

    /**
     * Create a new table
     */
    protected function createTable($tableName, callable $callback)
    {
        $table = new Blueprint($tableName);
        $callback($table);
        
        $sql = $table->toSql();
        $this->db->execute($sql);
    }

    /**
     * Drop a table
     */
    protected function dropTable($tableName)
    {
        $this->db->execute("DROP TABLE IF EXISTS {$tableName}");
    }

    /**
     * Modify a table
     */
    protected function table($tableName, callable $callback)
    {
        $table = new Blueprint($tableName, true);
        $callback($table);
        
        $sql = $table->toSql();
        if (!empty($sql)) {
            $this->db->execute($sql);
        }
    }
}