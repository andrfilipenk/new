<?php

namespace Core\Database;

/**
 * Schema Builder
 * 
 * Fluent interface for building database schemas
 */
class Schema
{
    protected string $table;
    protected array $columns = [];
    protected array $indexes = [];
    protected array $foreign = [];
    protected bool $modifyMode = false;
    protected ?string $engine = 'InnoDB';
    protected ?string $charset = 'utf8mb4';
    protected ?string $collation = 'utf8mb4_unicode_ci';
    
    public function __construct(string $table)
    {
        $this->table = $table;
    }
    
    public function setModifyMode(bool $mode): void
    {
        $this->modifyMode = $mode;
    }
    
    /**
     * Add incremental ID column
     */
    public function id(string $name = 'id'): static
    {
        $this->columns[] = "`{$name}` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY";
        return $this;
    }
    
    /**
     * Add string column
     */
    public function string(string $name, int $length = 255): Column
    {
        $column = new Column($name, "VARCHAR({$length})");
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add text column
     */
    public function text(string $name): Column
    {
        $column = new Column($name, 'TEXT');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add long text column
     */
    public function longText(string $name): Column
    {
        $column = new Column($name, 'LONGTEXT');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add integer column
     */
    public function integer(string $name): Column
    {
        $column = new Column($name, 'INT');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add big integer column
     */
    public function bigInteger(string $name): Column
    {
        $column = new Column($name, 'BIGINT');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add tiny integer column
     */
    public function tinyInteger(string $name): Column
    {
        $column = new Column($name, 'TINYINT');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add decimal column
     */
    public function decimal(string $name, int $precision = 10, int $scale = 2): Column
    {
        $column = new Column($name, "DECIMAL({$precision},{$scale})");
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add float column
     */
    public function float(string $name): Column
    {
        $column = new Column($name, 'FLOAT');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add double column
     */
    public function double(string $name): Column
    {
        $column = new Column($name, 'DOUBLE');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add boolean column
     */
    public function boolean(string $name): Column
    {
        $column = new Column($name, 'TINYINT(1)');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add date column
     */
    public function date(string $name): Column
    {
        $column = new Column($name, 'DATE');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add datetime column
     */
    public function datetime(string $name): Column
    {
        $column = new Column($name, 'DATETIME');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add timestamp column
     */
    public function timestamp(string $name): Column
    {
        $column = new Column($name, 'TIMESTAMP');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add time column
     */
    public function time(string $name): Column
    {
        $column = new Column($name, 'TIME');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add enum column
     */
    public function enum(string $name, array $values): Column
    {
        $values = array_map(function($v) { return "'{$v}'"; }, $values);
        $valuesList = implode(',', $values);
        $column = new Column($name, "ENUM({$valuesList})");
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add JSON column
     */
    public function json(string $name): Column
    {
        $column = new Column($name, 'JSON');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add foreign ID column
     */
    public function foreignId(string $name): Column
    {
        $column = new Column($name, 'BIGINT UNSIGNED');
        $this->columns[] = $column;
        return $column;
    }
    
    /**
     * Add timestamps (created_at, updated_at)
     */
    public function timestamps(): static
    {
        $this->columns[] = "`created_at` TIMESTAMP NULL DEFAULT NULL";
        $this->columns[] = "`updated_at` TIMESTAMP NULL DEFAULT NULL";
        return $this;
    }
    
    /**
     * Add soft delete timestamp
     */
    public function softDeletes(): static
    {
        $this->columns[] = "`deleted_at` TIMESTAMP NULL DEFAULT NULL";
        return $this;
    }
    
    /**
     * Add index
     */
    public function index(string|array $columns, $name = null): static
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        
        $name = $name ?? $this->table . '_' . implode('_', $columns) . '_index';
        $columnList = implode('`, `', $columns);
        
        $this->indexes[] = "INDEX `{$name}` (`{$columnList}`)";
        return $this;
    }
    
    /**
     * Add unique index
     */
    public function unique(string|array $columns, $name = null): static
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }
        
        $name = $name ?? $this->table . '_' . implode('_', $columns) . '_unique';
        $columnList = implode('`, `', $columns);
        
        $this->indexes[] = "UNIQUE INDEX `{$name}` (`{$columnList}`)";
        return $this;
    }
    
    /**
     * Add foreign key constraint
     */
    public function foreign(string $column): ForeignKey
    {
        $fk = new ForeignKey($this->table, $column);
        $this->foreign[] = $fk;
        return $fk;
    }
    
    /**
     * Create the table
     */
    public function create(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (\n";
        
        $definitions = [];
        
        // Add columns
        foreach ($this->columns as $column) {
            if ($column instanceof Column) {
                $definitions[] = $column->toSql();
            } else {
                $definitions[] = $column;
            }
        }
        
        // Add indexes
        $definitions = array_merge($definitions, $this->indexes);
        
        // Add foreign keys
        foreach ($this->foreign as $fk) {
            $definitions[] = $fk->toSql();
        }
        
        $sql .= "  " . implode(",\n  ", $definitions);
        $sql .= "\n)";
        
        if ($this->engine) {
            $sql .= " ENGINE={$this->engine}";
        }
        
        if ($this->charset) {
            $sql .= " DEFAULT CHARSET={$this->charset}";
        }
        
        if ($this->collation) {
            $sql .= " COLLATE={$this->collation}";
        }
        
        DB::connection()->statement($sql);
    }
    
    /**
     * Alter existing table
     */
    public function alter(): void
    {
        $statements = [];
        
        // Add columns
        foreach ($this->columns as $column) {
            if ($column instanceof Column) {
                $statements[] = "ADD COLUMN " . $column->toSql();
            } else {
                $statements[] = "ADD COLUMN " . $column;
            }
        }
        
        // Add indexes
        foreach ($this->indexes as $index) {
            $statements[] = "ADD " . $index;
        }
        
        // Add foreign keys
        foreach ($this->foreign as $fk) {
            $statements[] = "ADD " . $fk->toSql();
        }
        
        if (!empty($statements)) {
            $sql = "ALTER TABLE `{$this->table}`\n  " . implode(",\n  ", $statements);
            DB::connection()->statement($sql);
        }
    }
}

/**
 * Column Definition
 */
class Column
{
    protected string $name;
    protected string $type;
    protected bool $nullable = false;
    protected $default = null;
    protected bool $unsigned = false;
    protected bool $autoIncrement = false;
    protected ?string $comment = null;
    protected ?string $after = null;
    protected bool $unique = false;
    
    public function __construct(string $name, string $type)
    {
        $this->name = $name;
        $this->type = $type;
    }
    
    public function nullable(): static
    {
        $this->nullable = true;
        return $this;
    }
    
    public function default($value): static
    {
        $this->default = $value;
        return $this;
    }
    
    public function unsigned(): static
    {
        $this->unsigned = true;
        return $this;
    }
    
    public function autoIncrement(): static
    {
        $this->autoIncrement = true;
        return $this;
    }
    
    public function comment(string $comment): static
    {
        $this->comment = $comment;
        return $this;
    }
    
    public function after(string $column): static
    {
        $this->after = $column;
        return $this;
    }
    
    public function unique(): static
    {
        $this->unique = true;
        return $this;
    }
    
    public function toSql(): string
    {
        $sql = "`{$this->name}` {$this->type}";
        
        if ($this->unsigned) {
            $sql .= " UNSIGNED";
        }
        
        if (!$this->nullable) {
            $sql .= " NOT NULL";
        } else {
            $sql .= " NULL";
        }
        
        if ($this->default !== null) {
            if ($this->default === 'CURRENT_TIMESTAMP') {
                $sql .= " DEFAULT CURRENT_TIMESTAMP";
            } elseif (is_string($this->default)) {
                $sql .= " DEFAULT '{$this->default}'";
            } elseif (is_bool($this->default)) {
                $sql .= " DEFAULT " . ($this->default ? '1' : '0');
            } else {
                $sql .= " DEFAULT {$this->default}";
            }
        }
        
        if ($this->autoIncrement) {
            $sql .= " AUTO_INCREMENT";
        }
        
        if ($this->comment) {
            $sql .= " COMMENT '{$this->comment}'";
        }
        
        if ($this->after) {
            $sql .= " AFTER `{$this->after}`";
        }
        
        if ($this->unique) {
            $sql .= " UNIQUE";
        }
        
        return $sql;
    }
}

/**
 * Foreign Key Definition
 */
class ForeignKey
{
    protected string $table;
    protected string $column;
    protected ?string $references = null;
    protected ?string $on = null;
    protected string $onDelete = 'RESTRICT';
    protected string $onUpdate = 'RESTRICT';
    
    public function __construct(string $table, string $column)
    {
        $this->table = $table;
        $this->column = $column;
    }
    
    public function references(string $column): static
    {
        $this->references = $column;
        return $this;
    }
    
    public function on(string $table): static
    {
        $this->on = $table;
        return $this;
    }
    
    public function onDelete(string $action): static
    {
        $this->onDelete = $action;
        return $this;
    }
    
    public function onUpdate(string $action): static
    {
        $this->onUpdate = $action;
        return $this;
    }
    
    public function cascadeOnDelete(): static
    {
        $this->onDelete = 'CASCADE';
        return $this;
    }
    
    public function nullOnDelete(): static
    {
        $this->onDelete = 'SET NULL';
        return $this;
    }
    
    public function toSql(): string
    {
        $name = "fk_{$this->table}_{$this->column}";
        
        $sql = "CONSTRAINT `{$name}` FOREIGN KEY (`{$this->column}`) ";
        $sql .= "REFERENCES `{$this->on}` (`{$this->references}`)";
        $sql .= " ON DELETE {$this->onDelete}";
        $sql .= " ON UPDATE {$this->onUpdate}";
        
        return $sql;
    }
}
