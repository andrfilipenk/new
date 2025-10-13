<?php
// app/Core/Database/Migration.php
namespace Core\Database;

use Core\Di\Container;
use Core\Di\Injectable;

/**
 * Base Migration class
 */
abstract class Migration
{
    use Injectable;
    
    static public function db() : Database {
        return Container::getDefault()->get('db');
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
        $blueprint = new Blueprint($tableName);
        $callback($blueprint);
        $sql = $blueprint->toSql();
        self::db()->execute($sql);
    }

    /**
     * Drop a table
     */
    protected function dropTable($tableName)
    {
        self::db()->execute("DROP TABLE IF EXISTS {$tableName}");
    }

    /**
     * Modify a table
     */
    protected function table($tableName, callable $callback)
    {
        $table = new Blueprint($tableName);
        $callback($table);
        $sql = $table->toSql();
        if (!empty($sql)) {
            self::db()->execute($sql);
        }
    }

    protected function insertData($table, $data = [])
    {
        $query = self::db()->table($table);
        $query->insert($data);
        return $this;
    }

    /**
     * Insert data array
     */
    protected function insertDataArray($table, $columns, $values)
    {
        $query = self::db()->table($table);
        foreach ($values as $row) {
            $data = array_combine($columns, $row);
            $query->insert($data);
        }
        return $this;
    }
}