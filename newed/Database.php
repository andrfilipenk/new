<?php
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
        'select' => ['*'],
        'where' => [],
        'bindings' => [],
        'order' => [],
        'limit' => null,
        'offset' => null,
        'join' => []
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
        $new->query = [
            'select' => ['*'],
            'where' => [],
            'bindings' => [],
            'order' => [],
            'limit' => null,
            'offset' => null,
            'join' => []
        ];
        return $new;
    }

    public function select(array|string $columns = '*'): self
    {
        $this->query['select'] = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function where(string $column, mixed $operator = null, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query['where'][] = [$column, $operator, $value, 'AND'];
        if ($value !== null) {
            $this->query['bindings'][] = $value;
        }

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) return $this;
        
        $this->query['where'][] = [$column, 'IN', $values, 'AND'];
        $this->query['bindings'] = [...$this->query['bindings'], ...$values];
        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->query['join'][] = "INNER JOIN {$table} ON {$first} {$operator} {$second}";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->query['order'][] = "{$column} " . strtoupper($direction);
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
        return $this->execute($this->buildSelect())->fetchAll();
    }

    public function first(): ?array
    {
        $result = $this->limit(1)->get();
        return $result[0] ?? null;
    }

    public function insert(array $data): string
    {
        $columns = implode(',', array_keys($data));
        $placeholders = str_repeat('?,', count($data) - 1) . '?';
        
        $this->execute("INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})", array_values($data));
        return $this->pdo->lastInsertId();
    }

    public function update(array $data): int
    {
        $set = implode(' = ?,', array_keys($data)) . ' = ?';
        $sql = "UPDATE {$this->table} SET {$set}" . $this->buildWhere();
        
        return $this->execute($sql, [...array_values($data), ...$this->query['bindings']])->rowCount();
    }

    public function delete(): int
    {
        return $this->execute("DELETE FROM {$this->table}" . $this->buildWhere(), $this->query['bindings'])->rowCount();
    }

    private function buildSelect(): string
    {
        $sql = "SELECT " . implode(',', $this->query['select']) . " FROM {$this->table}";
        
        if ($this->query['join']) {
            $sql .= ' ' . implode(' ', $this->query['join']);
        }

        $sql .= $this->buildWhere();

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
        if (!$this->query['where']) return '';

        $conditions = [];
        foreach ($this->query['where'] as $i => [$column, $operator, $value, $boolean]) {
            $prefix = $i > 0 ? " {$boolean} " : '';
            
            if ($operator === 'IN') {
                $placeholders = str_repeat('?,', count($value) - 1) . '?';
                $conditions[] = "{$prefix}{$column} IN ({$placeholders})";
            } else {
                $conditions[] = "{$prefix}{$column} {$operator} ?";
            }
        }

        return ' WHERE ' . implode('', $conditions);
    }

    private function execute(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new DatabaseException("Query failed: {$e->getMessage()}");
        }
    }

    public function beginTransaction(): bool { return $this->pdo->beginTransaction(); }
    public function commit(): bool { return $this->pdo->commit(); }
    public function rollback(): bool { return $this->pdo->rollBack(); }
    public function getPdo(): PDO { return $this->pdo; }
}