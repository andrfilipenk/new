<?php
// app/Core/Database/Database.php
namespace Core\Database;

use PDO;
use PDOException;
use Core\Di\Injectable;

class Database
{
    use Injectable;

    private ?PDO $pdo = null;
    
    private string $table = '';

    private array $query = [
        'select'    => ['*'],
        'where'     => [],
        'bindings'  => [],
        'order'     => [],
        'limit'     => null,
        'offset'    => null,
        'join'      => []
    ];

    public function __construct()
    {
        $this->connect();
    }

    private function connect(): void
    {
        if ($this->pdo) return;
        try {
            $config = $this->getDI()->get('config')['db'];
            $dsn = "{$config['driver']}:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'"
            ]);
        } catch (PDOException $e) {
            throw new DatabaseException("Connection failed: " . $e->getMessage());
        }
    }

    public function table(string $table): self
    {
        $new = clone $this;
        $new->table = $table;
        $new->resetQuery();
        return $new;
    }

    private function resetQuery(): void
    {
        $this->query = [
            'select'    => ['*'],
            'where'     => [],
            'bindings'  => [],
            'order'     => [],
            'limit'     => null,
            'offset'    => null,
            'join'      => [],
            'group'     => [],
            'having'    => []
        ];
    }

    public function select(array|string $columns = '*'): self
    {
        $this->query['select'] = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function selectRaw(string $expression): self
    {
        $this->query['select'] = [$expression];
        return $this;
    }

    public function where(string $column, mixed $operator = null, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $this->query['where'][] = [$column, $operator, $value, 'AND'];
        // Only add binding if value is not null and not an array (for IN clause)
        if ($value !== null && !is_array($value)) {
            $this->query['bindings'][] = $value;
        }
        return $this;
    }

    public function orWhere(string $column, mixed $operator = null, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $this->query['where'][] = [$column, $operator, $value, 'OR'];
        if ($value !== null && !is_array($value)) {
            $this->query['bindings'][] = $value;
        }
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) return $this;
        $this->query['where'][] = [$column, 'IN', $values, 'AND'];
        $this->query['bindings'] = array_merge($this->query['bindings'], array_values($values));
        return $this;
    }

    public function whereNotIn(string $column, array $values): self
    {
        if (empty($values)) return $this;
        $this->query['where'][] = [$column, 'NOT IN', $values, 'AND'];
        $this->query['bindings'] = array_merge($this->query['bindings'], array_values($values));
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->query['where'][] = [$column, 'IS NULL', null, 'AND'];
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->query['where'][] = [$column, 'IS NOT NULL', null, 'AND'];
        return $this;
    }

    public function whereRaw(string $sql, array $bindings = []): self
    {
        $this->query['where'][] = ['RAW', $sql, $bindings, 'AND'];
        $this->query['bindings'] = array_merge($this->query['bindings'], $bindings);
        return $this;
    }

    public function groupBy(string $column): self
    {
        $this->query['group'][] = $column;
        return $this;
    }

    public function having(string $column, string $operator = null, $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $this->query['having'][] = [$column, $operator, $value];
        if ($value !== null) {
            $this->query['bindings'][] = $value;
        }
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->query['join'][] = "LEFT JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->query['join'][] = "INNER JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->query['order'][] = $column . ' ' . strtoupper($direction);
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->query['limit'] = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->query['offset'] = $offset;
        return $this;
    }

    public function get(): array
    {
        $sql = $this->buildSelect();
        return $this->execute($sql, $this->query['bindings'])->fetchAll();
    }

    public function first(): ?array
    {
        $this->limit(1);
        $result = $this->get();
        return $result[0] ?? null;
    }

    public function count(): int
    {
        $originalSelect = $this->query['select'];
        $this->query['select'] = ['COUNT(*)'];
        $result = $this->first();
        $this->query['select'] = $originalSelect;
        return (int) ($result['COUNT(*)'] ?? 0);
    }

    public function insert(array $data): string
    {
        if (empty($data)) {
            throw new DatabaseException("Cannot insert empty data");
        }
        $columns = implode(',', array_keys($data));
        $placeholders = str_repeat('?,', count($data) - 1) . '?';
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $this->execute($sql, array_values($data));
        return $this->pdo->lastInsertId();
    }

    public function update(array $data): int
    {
        if (empty($data)) {
            throw new DatabaseException("Cannot update with empty data");
        }
        $set = implode(' = ?,', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$this->table} SET {$set}" . $this->buildWhere();
        $bindings = array_merge(array_values($data), $this->query['bindings']);
        return $this->execute($sql, $bindings)->rowCount();
    }

    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}" . $this->buildWhere();
        return $this->execute($sql, $this->query['bindings'])->rowCount();
    }

    private function buildSelect(): string
    {
        $sql = "SELECT " . implode(',', $this->query['select']) . " FROM {$this->table}";
        if ($this->query['join']) {
            $sql .= ' ' . implode(' ', $this->query['join']);
        }
        $sql .= $this->buildWhere();
        if ($this->query['group']) {
            $sql .= ' GROUP BY ' . implode(',', $this->query['group']);
        }
        if ($this->query['having']) {
            $sql .= $this->buildHaving();
        }
        if ($this->query['order']) {
            $sql .= ' ORDER BY ' . implode(',', $this->query['order']);
        }
        if ($this->query['limit']) {
            $sql .= " LIMIT {$this->query['limit']}";
            if ($this->query['offset']) {
                $sql .= " OFFSET {$this->query['offset']}";
            }
        }
        return $sql;
    }

    private function buildWhere(): string
    {
        if (empty($this->query['where'])) {
            return '';
        }
        $conditions = [];
        foreach ($this->query['where'] as $i => [$column, $operator, $value, $boolean]) {
            $prefix = $i > 0 ? " {$boolean} " : '';
            if ($operator === 'RAW') {
                $conditions[] = "{$prefix}{$column}";
            } elseif ($operator === 'IN' || $operator === 'NOT IN') {
                if (is_array($value) && !empty($value)) {
                    $placeholders = str_repeat('?,', count($value) - 1) . '?';
                    $conditions[] = "{$prefix}{$column} {$operator} ({$placeholders})";
                }
            } elseif ($operator === 'IS NULL' || $operator === 'IS NOT NULL') {
                $conditions[] = "{$prefix}{$column} {$operator}";
            } else {
                $conditions[] = "{$prefix}{$column} {$operator} ?";
            }
        }
        return $conditions ? ' WHERE ' . implode('', $conditions) : '';
    }

    private function buildHaving(): string
    {
        if (empty($this->query['having'])) {
            return '';
        }
        $conditions = [];
        foreach ($this->query['having'] as $i => [$column, $operator, $value]) {
            $prefix = $i > 0 ? ' AND ' : '';
            $conditions[] = "{$prefix}{$column} {$operator} ?";
        }
        return $conditions ? ' HAVING ' . implode('', $conditions) : '';
    }

    public function execute(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Debug information
            $paramCount     = substr_count($sql, '?');
            $providedCount  = count($params);
            throw new DatabaseException(
                "Query failed: {$e->getMessage()}\n" .
                "SQL: {$sql}\n" .
                "Expected parameters: {$paramCount}, Provided: {$providedCount}\n" .
                "Parameters: " . json_encode($params)
            );
        }
    }

    public function beginTransaction(): bool { return $this->pdo->beginTransaction(); }
    public function commit(): bool { return $this->pdo->commit(); }
    public function rollback(): bool { return $this->pdo->rollBack(); }
    public function getPdo(): PDO { return $this->pdo; }
}
