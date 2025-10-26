<?php

namespace Core\Database;

/**
 * Query Builder
 * 
 * Fluent interface for SQL query construction
 */
class QueryBuilder
{
    protected Connection $connection;
    protected string $table;
    protected array $select = ['*'];
    protected bool $distinct = false;
    protected array $where = [];
    protected array $bindings = [];
    protected array $joins = [];
    protected array $groupBy = [];
    protected array $having = [];
    protected array $orderBy = [];
    protected ?int $limit = null;
    protected ?int $offset = null;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Set table
     */
    public function table(string $table): static
    {
        $this->table = $this->connection->getPrefix() . $table;
        return $this;
    }

    /**
     * Set table (alias)
     */
    public function from(string $table): static
    {
        return $this->table($table);
    }

    /**
     * Set select columns
     */
    public function select(...$columns): static
    {
        $this->select = $columns;
        return $this;
    }

    /**
     * Add raw select expression
     */
    public function selectRaw(string $expression, array $bindings = []): static
    {
        $this->select[] = $expression;
        $this->bindings = array_merge($this->bindings, $bindings);
        return $this;
    }

    /**
     * Set distinct
     */
    public function distinct(): static
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Add WHERE clause
     */
    public function where($column, $operator = null, $value = null, string $boolean = 'AND'): static
    {
        // Handle where($column, $value) syntax
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->where[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean,
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Add OR WHERE clause
     */
    public function orWhere($column, $operator = null, $value = null): static
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Add WHERE IN clause
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND'): static
    {
        $this->where[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
        ];

        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    /**
     * Add WHERE NOT IN clause
     */
    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): static
    {
        $this->where[] = [
            'type' => 'notIn',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
        ];

        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    /**
     * Add WHERE BETWEEN clause
     */
    public function whereBetween(string $column, array $values, string $boolean = 'AND'): static
    {
        $this->where[] = [
            'type' => 'between',
            'column' => $column,
            'values' => $values,
            'boolean' => $boolean,
        ];

        $this->bindings = array_merge($this->bindings, $values);

        return $this;
    }

    /**
     * Add WHERE NULL clause
     */
    public function whereNull(string $column, string $boolean = 'AND'): static
    {
        $this->where[] = [
            'type' => 'null',
            'column' => $column,
            'boolean' => $boolean,
        ];

        return $this;
    }

    /**
     * Add WHERE NOT NULL clause
     */
    public function whereNotNull(string $column, string $boolean = 'AND'): static
    {
        $this->where[] = [
            'type' => 'notNull',
            'column' => $column,
            'boolean' => $boolean,
        ];

        return $this;
    }

    /**
     * Add raw WHERE clause
     */
    public function whereRaw(string $sql, array $bindings = [], string $boolean = 'AND'): static
    {
        $this->where[] = [
            'type' => 'raw',
            'sql' => $sql,
            'boolean' => $boolean,
        ];

        $this->bindings = array_merge($this->bindings, $bindings);

        return $this;
    }

    /**
     * Add JOIN clause
     */
    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): static
    {
        $this->joins[] = [
            'type' => $type,
            'table' => $this->connection->getPrefix() . $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];

        return $this;
    }

    /**
     * Add LEFT JOIN
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add RIGHT JOIN
     */
    public function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Add CROSS JOIN
     */
    public function crossJoin(string $table): static
    {
        $this->joins[] = [
            'type' => 'CROSS',
            'table' => $this->connection->getPrefix() . $table,
        ];

        return $this;
    }

    /**
     * Add GROUP BY
     */
    public function groupBy(...$columns): static
    {
        $this->groupBy = array_merge($this->groupBy, $columns);
        return $this;
    }

    /**
     * Add HAVING clause
     */
    public function having(string $column, string $operator, $value): static
    {
        $this->having[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
        ];

        $this->bindings[] = $value;

        return $this;
    }

    /**
     * Add ORDER BY
     */
    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtoupper($direction),
        ];

        return $this;
    }

    /**
     * Order by latest (created_at DESC)
     */
    public function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Order by oldest (created_at ASC)
     */
    public function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'ASC');
    }

    /**
     * Set LIMIT
     */
    public function limit(int $value): static
    {
        $this->limit = $value;
        return $this;
    }

    /**
     * Set LIMIT (alias)
     */
    public function take(int $value): static
    {
        return $this->limit($value);
    }

    /**
     * Set OFFSET
     */
    public function offset(int $value): static
    {
        $this->offset = $value;
        return $this;
    }

    /**
     * Set OFFSET (alias)
     */
    public function skip(int $value): static
    {
        return $this->offset($value);
    }

    /**
     * Get count
     */
    public function count(string $column = '*'): int
    {
        return (int) $this->aggregate('COUNT', $column);
    }

    /**
     * Get sum
     */
    public function sum(string $column): float
    {
        return (float) $this->aggregate('SUM', $column);
    }

    /**
     * Get average
     */
    public function avg(string $column): float
    {
        return (float) $this->aggregate('AVG', $column);
    }

    /**
     * Get minimum
     */
    public function min(string $column)
    {
        return $this->aggregate('MIN', $column);
    }

    /**
     * Get maximum
     */
    public function max(string $column)
    {
        return $this->aggregate('MAX', $column);
    }

    /**
     * Execute aggregate function
     */
    protected function aggregate(string $function, string $column)
    {
        $originalSelect = $this->select;
        $this->select = ["{$function}({$column}) as aggregate"];

        $result = $this->first();

        $this->select = $originalSelect;

        return $result['aggregate'] ?? 0;
    }

    /**
     * Execute query and get results
     */
    public function get(): array
    {
        $sql = $this->toSql();
        return $this->connection->select($sql, $this->bindings);
    }

    /**
     * Get first result
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Find by ID
     */
    public function find($id): ?array
    {
        return $this->where('id', '=', $id)->first();
    }

    /**
     * Get single column value
     */
    public function value(string $column)
    {
        $result = $this->first();
        return $result[$column] ?? null;
    }

    /**
     * Get array of column values
     */
    public function pluck(string $column): array
    {
        $results = $this->get();
        return array_column($results, $column);
    }

    /**
     * Check if records exist
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Insert record
     */
    public function insert(array $data): int
    {
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = array_fill(0, count($values), '?');

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        return $this->connection->insert($sql, $values);
    }

    /**
     * Insert and get ID
     */
    public function insertGetId(array $data): int
    {
        return $this->insert($data);
    }

    /**
     * Update records
     */
    public function update(array $data): int
    {
        $sets = [];
        $values = [];

        foreach ($data as $column => $value) {
            $sets[] = "{$column} = ?";
            $values[] = $value;
        }

        $sql = sprintf(
            'UPDATE %s SET %s%s',
            $this->table,
            implode(', ', $sets),
            $this->buildWhere()
        );

        $bindings = array_merge($values, $this->bindings);

        return $this->connection->update($sql, $bindings);
    }

    /**
     * Increment column
     */
    public function increment(string $column, int $amount = 1, array $extra = []): int
    {
        $extra[$column] = new \stdClass(); // Placeholder for raw SQL
        $sets = ["{$column} = {$column} + {$amount}"];

        foreach ($extra as $col => $value) {
            if (!$value instanceof \stdClass) {
                $sets[] = "{$col} = ?";
                $this->bindings[] = $value;
            }
        }

        $sql = sprintf(
            'UPDATE %s SET %s%s',
            $this->table,
            implode(', ', $sets),
            $this->buildWhere()
        );

        return $this->connection->update($sql, $this->bindings);
    }

    /**
     * Decrement column
     */
    public function decrement(string $column, int $amount = 1, array $extra = []): int
    {
        return $this->increment($column, -$amount, $extra);
    }

    /**
     * Delete records
     */
    public function delete($id = null): int
    {
        if ($id !== null) {
            $this->where('id', '=', $id);
        }

        $sql = sprintf('DELETE FROM %s%s', $this->table, $this->buildWhere());

        return $this->connection->delete($sql, $this->bindings);
    }

    /**
     * Build SQL query
     */
    public function toSql(): string
    {
        $distinct = $this->distinct ? 'DISTINCT ' : '';
        $select = implode(', ', $this->select);

        $sql = "SELECT {$distinct}{$select} FROM {$this->table}";

        $sql .= $this->buildJoins();
        $sql .= $this->buildWhere();
        $sql .= $this->buildGroupBy();
        $sql .= $this->buildHaving();
        $sql .= $this->buildOrderBy();
        $sql .= $this->buildLimit();

        return $sql;
    }

    /**
     * Build JOIN clauses
     */
    protected function buildJoins(): string
    {
        if (empty($this->joins)) {
            return '';
        }

        $sql = '';
        foreach ($this->joins as $join) {
            if ($join['type'] === 'CROSS') {
                $sql .= " CROSS JOIN {$join['table']}";
            } else {
                $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
            }
        }

        return $sql;
    }

    /**
     * Build WHERE clause
     */
    protected function buildWhere(): string
    {
        if (empty($this->where)) {
            return '';
        }

        $sql = ' WHERE ';
        $clauses = [];

        foreach ($this->where as $index => $where) {
            $boolean = $index === 0 ? '' : " {$where['boolean']} ";

            switch ($where['type']) {
                case 'basic':
                    $clauses[] = "{$boolean}{$where['column']} {$where['operator']} ?";
                    break;
                case 'in':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $clauses[] = "{$boolean}{$where['column']} IN ({$placeholders})";
                    break;
                case 'notIn':
                    $placeholders = implode(', ', array_fill(0, count($where['values']), '?'));
                    $clauses[] = "{$boolean}{$where['column']} NOT IN ({$placeholders})";
                    break;
                case 'between':
                    $clauses[] = "{$boolean}{$where['column']} BETWEEN ? AND ?";
                    break;
                case 'null':
                    $clauses[] = "{$boolean}{$where['column']} IS NULL";
                    break;
                case 'notNull':
                    $clauses[] = "{$boolean}{$where['column']} IS NOT NULL";
                    break;
                case 'raw':
                    $clauses[] = "{$boolean}{$where['sql']}";
                    break;
            }
        }

        return $sql . implode('', $clauses);
    }

    /**
     * Build GROUP BY clause
     */
    protected function buildGroupBy(): string
    {
        if (empty($this->groupBy)) {
            return '';
        }

        return ' GROUP BY ' . implode(', ', $this->groupBy);
    }

    /**
     * Build HAVING clause
     */
    protected function buildHaving(): string
    {
        if (empty($this->having)) {
            return '';
        }

        $clauses = [];
        foreach ($this->having as $having) {
            $clauses[] = "{$having['column']} {$having['operator']} ?";
        }

        return ' HAVING ' . implode(' AND ', $clauses);
    }

    /**
     * Build ORDER BY clause
     */
    protected function buildOrderBy(): string
    {
        if (empty($this->orderBy)) {
            return '';
        }

        $clauses = [];
        foreach ($this->orderBy as $order) {
            $clauses[] = "{$order['column']} {$order['direction']}";
        }

        return ' ORDER BY ' . implode(', ', $clauses);
    }

    /**
     * Build LIMIT clause
     */
    protected function buildLimit(): string
    {
        $sql = '';

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * Clone query builder
     */
    public function __clone()
    {
        // Allow cloning for reusable queries
    }
}
