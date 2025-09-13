<?php
namespace Core\Database;

/**
 * Schema Blueprint for table creation and modification
 */
class Blueprint
{
    protected $table;
    protected $columns = [];
    protected $commands = [];
    protected $modifying = false;

    public function __construct($table, $modifying = false)
    {
        $this->table = $table;
        $this->modifying = $modifying;
    }

    public function id($column = 'id')
    {
        return $this->integer($column, true);
    }

    public function integer($column, $autoIncrement = false, $primaryKey = false)
    {
        $this->columns[] = [
            'name' => $column,
            'type' => 'INT',
            'auto_increment' => $autoIncrement,
            'primary_key' => $primaryKey
        ];
        return $this;
    }

    public function string($column, $length = 255)
    {
        $this->columns[] = [
            'name' => $column,
            'type' => "VARCHAR($length)"
        ];
        return $this;
    }

    public function text($column)
    {
        $this->columns[] = [
            'name' => $column,
            'type' => 'TEXT'
        ];
        return $this;
    }

    public function timestamp($column)
    {
        $this->columns[] = [
            'name' => $column,
            'type' => 'TIMESTAMP'
        ];
        return $this;
    }

    public function timestamps()
    {
        $this->timestamp('created_at')->nullable();
        $this->timestamp('updated_at')->nullable();
    }

    public function nullable()
    {
        $lastColumn = end($this->columns);
        $lastColumn['nullable'] = true;
        $this->columns[key($this->columns)] = $lastColumn;
        return $this;
    }

    public function default($value)
    {
        $lastColumn = end($this->columns);
        $lastColumn['default'] = $value;
        $this->columns[key($this->columns)] = $lastColumn;
        return $this;
    }

    public function unique()
    {
        $lastColumn = end($this->columns);
        $this->commands[] = [
            'type' => 'unique',
            'column' => $lastColumn['name']
        ];
        return $this;
    }

    public function foreign($column)
    {
        $command = [
            'type' => 'foreign',
            'column' => $column
        ];
        $this->commands[] = $command;
        return $this;
    }

    public function references($column)
    {
        $lastCommand = end($this->commands);
        $lastCommand['references'] = $column;
        $this->commands[key($this->commands)] = $lastCommand;
        return $this;
    }

    public function on($table)
    {
        $lastCommand = end($this->commands);
        $lastCommand['on'] = $table;
        $this->commands[key($this->commands)] = $lastCommand;
        return $this;
    }

    public function toSql()
    {
        if ($this->modifying) {
            return $this->toAlterSql();
        }
        return $this->toCreateSql();
    }

    protected function toCreateSql()
    {
        $sql = "CREATE TABLE {$this->table} (";
        $columns = [];
        foreach ($this->columns as $column) {
            $columnSql = "{$column['name']} {$column['type']}";
            if (isset($column['nullable']) && $column['nullable']) {
                $columnSql .= " NULL";
            } else {
                $columnSql .= " NOT NULL";
            }
            if (isset($column['default'])) {
                $default = is_string($column['default']) ? "'{$column['default']}'" : $column['default'];
                $columnSql .= " DEFAULT {$default}";
            }
            if (isset($column['auto_increment']) && $column['auto_increment']) {
                $columnSql .= " AUTO_INCREMENT";
            }
            if (isset($column['primary_key']) && $column['primary_key']) {
                $columnSql .= " PRIMARY KEY";
            }
            $columns[] = $columnSql;
        }
        $sql .= implode(', ', $columns);
        // Add unique constraints
        foreach ($this->commands as $command) {
            if ($command['type'] === 'unique') {
                $sql .= ", UNIQUE ({$command['column']})";
            }
        }
        // Add foreign key constraints
        foreach ($this->commands as $command) {
            if ($command['type'] === 'foreign') {
                $sql .= ", FOREIGN KEY ({$command['column']}) REFERENCES {$command['on']}({$command['references']})";
            }
        }
        $sql .= ")";
        return $sql;
    }

    protected function toAlterSql()
    {
        $sql = [];
        foreach ($this->columns as $column) {
            $columnSql = "ALTER TABLE {$this->table} ADD COLUMN {$column['name']} {$column['type']}";
            if (isset($column['nullable']) && $column['nullable']) {
                $columnSql .= " NULL";
            } else {
                $columnSql .= " NOT NULL";
            }
            if (isset($column['default'])) {
                $default = is_string($column['default']) ? "'{$column['default']}'" : $column['default'];
                $columnSql .= " DEFAULT {$default}";
            }
            $sql[] = $columnSql;
        }
        foreach ($this->commands as $command) {
            if ($command['type'] === 'unique') {
                $sql[] = "ALTER TABLE {$this->table} ADD UNIQUE ({$command['column']})";
            } elseif ($command['type'] === 'foreign') {
                $sql[] = "ALTER TABLE {$this->table} ADD FOREIGN KEY ({$command['column']}) REFERENCES {$command['on']}({$command['references']})";
            }
        }
        return implode('; ', $sql);
    }
}