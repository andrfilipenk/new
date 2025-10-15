<?php
// app/Core/Database/Blueprint.php
namespace Core\Database;

class Blueprint
{
    protected $table;
    protected $columns  = [];
    protected $commands = [];

    public function __construct($table)
    {
        $this->table = $table;
    }

    protected function addColumn(string $type, string $name): ColumnDefinition
    {
        $column = new ColumnDefinition([
            'type' => $type,
            'name' => $name,
        ]);
        $this->columns[] = $column;
        return $column;
    }

    public function id(string $name = 'id'): ColumnDefinition
    {
        return $this->addColumn('INT', $name)->unsigned()->autoIncrement()->primary();
    }

    public function string(string $name, int $length = 255): ColumnDefinition
    {
        return $this->addColumn("VARCHAR({$length})", $name);
    }

    public function integer(string $name): ColumnDefinition
    {
        return $this->addColumn('INT', $name);
    }

    public function decimal(string $name, int $total = 8, int $places = 2): ColumnDefinition
    {
        return $this->addColumn("DECIMAL({$total},{$places})", $name);
    }

    public function text(string $name): ColumnDefinition
    {
        return $this->addColumn('TEXT', $name);
    }

    public function date(string $name): ColumnDefinition
    {
        return $this->addColumn('DATE', $name);
    }

    public function timestamp(string $name): ColumnDefinition
    {
        return $this->addColumn('TIMESTAMP', $name);
    }

    public function timestamps(): void
    {
        $this->timestamp('created_at')->default('CURRENT_TIMESTAMP');
        $this->timestamp('updated_at')->default('CURRENT_TIMESTAMP');
    }

    public function foreign(string $column): ColumnDefinition
    {
        return $this->addColumn('FOREIGN', $column);
    }

    public function index($columns, $name = null, $type = 'INDEX'): self
    {
        $columns = (array) $columns;
        $name = $name ?? 'idx_' . $this->table . '_' . implode('_', $columns);
        $this->columns[] = new ColumnDefinition([
            'type' => $type,
            'name' => $name,
            'columns' => $columns
        ]);
        return $this;
    }

    /*
    public function toSql(): string
    {
        $columnsSql     = [];
        $primaryKeys    = [];
        $foreignKeys    = [];
        foreach ($this->columns as $column) {
            if ($column->get('type') === 'FOREIGN') {
                $foreignKeys[] = $column->toSql();
                continue;
            }
            $columnsSql[] = $column->toSql();
            if ($column->get('primary')) {
                $primaryKeys[] = $column->get('name');
            }
        }
        $sql = "CREATE TABLE `{$this->table}` (\n";
        $sql .= "    " . implode(",\n    ", $columnsSql);
        if (!empty($primaryKeys)) {
            $sql .= ",\n    PRIMARY KEY (`" . implode('`, `', $primaryKeys) . "`)";
        }
        if (!empty($foreignKeys)) {
            $sql .= ",\n    " . implode(",\n    ", $foreignKeys);
        }
        $sql .= "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        return $sql;
    }*/

    public function toSql(): string
    {
        $columns = $primaries = $foreigns = $indexes = [];
        foreach ($this->columns as $column) {
            $type = $column->get('type');
            if ($type === 'FOREIGN') {
                $foreigns[] = $column->toSql();
            } elseif ($type === 'INDEX' || $type === 'UNIQUE') {
                $indexes[] = $column->toSql();
            } else {
                $columns[] = $column->toSql();
            }
            if ($column->get('primary')) {
                $primaries[] = $column->get('name');
            }
        }
        $sql = "CREATE TABLE `{$this->table}` (\n    " . implode(",\n    ", $columns);
        if ($primaries) {
            $sql .= ",\n    PRIMARY KEY (`" . implode('`,`', $primaries) . "`)";
        }
        if ($foreigns) {
            $sql .= ",\n    " . implode(",\n    ", $foreigns);
        }
        if ($indexes) {
            $sql .= ",\n    " . implode(",\n    ", $indexes);
        }
        return "$sql\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    }
}