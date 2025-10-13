<?php
// app/Core/Database/ColumnDefinition.php
namespace Core\Database;

class ColumnDefinition
{
    protected $attributes = [];

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    public function get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function nullable(): self
    {
        $this->attributes['nullable'] = true;
        return $this;
    }

    public function default($value): self
    {
        $this->attributes['default'] = $value;
        return $this;
    }

    public function unsigned(): self
    {
        $this->attributes['unsigned'] = true;
        return $this;
    }

    public function autoIncrement(): self
    {
        $this->attributes['auto_increment'] = true;
        return $this;
    }

    public function primary(): self
    {
        $this->attributes['primary'] = true;
        return $this;
    }

    public function unique(): self
    {
        $this->attributes['unique'] = true;
        return $this;
    }

    public function references(string $column): self
    {
        $this->attributes['references'] = $column;
        return $this;
    }

    public function on(string $table): self
    {
        $this->attributes['on'] = $table;
        return $this;
    }

    public function toSql(): string
    {
        if ($this->get('type') === 'FOREIGN') {
            return sprintf(
                "FOREIGN KEY (`%s`) REFERENCES `%s`(`%s`)",
                $this->get('name'),
                $this->get('on'),
                $this->get('references')
            );
        }
        $sql = "`{$this->get('name')}` {$this->get('type')}";
        if ($this->get('unsigned')) $sql .= ' UNSIGNED';
        $sql .= $this->get('nullable') ? ' NULL' : ' NOT NULL';
        if ($this->get('auto_increment')) $sql .= ' AUTO_INCREMENT';
        if ($this->get('unique')) $sql .= ' UNIQUE';
        if (array_key_exists('default', $this->attributes)) {
            $default = $this->get('default');
            if (is_string($default)) {
                if ($default === 'CURRENT_TIMESTAMP') {
                    $sql .= " DEFAULT {$default}";
                } else {
                    $sql .= " DEFAULT '{$default}'";
                }
            } else if ($default === null) {
                $sql .= " DEFAULT NULL";
            } else {
                $sql .= " DEFAULT {$default}";
            }
        }
        return $sql;
    }
}