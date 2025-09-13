<?php
namespace Core\Database;

use PDO;
use PDOException;
use Core\Di\Injectable;

/**
 * Database Component for Lightweight PHP Framework
 */
class Database
{
    use Injectable;

    protected $pdo;
    protected $query;
    protected $table;
    protected $params = [];
    protected $connection;

    // Query parts
    protected $select = '*';
    protected $where = [];
    protected $orderBy = '';
    protected $limit = '';
    protected $offset = '';

    /**
     * Constructor - initializes database connection
     */
    public function __construct()
    {
        $this->connect();
    }

    /**
     * Establish database connection
     */
    protected function connect()
    {
        try {
            $config = $this->getDI()->get('config');
            $cfg = $config['db'];
            $dsn = "{$cfg['driver']}:host={$cfg['host']};dbname={$cfg['database']};charset={$cfg['charset']}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => $cfg['persistent'],
            ];

            $this->pdo = new PDO(
                $dsn, 
                $cfg['username'], 
                $cfg['password'], 
                $options
            );

        } catch (PDOException $e) {
            throw new DatabaseException("Connection failed: " . $e->getMessage());
        }
    }

    /**
     * Set table for query
     */
    public function table($table)
    {
        $this->table = $table;
        $this->reset();
        return $this;
    }

    /**
     * Select columns
     */
    public function select($columns)
    {
        $this->select = is_array($columns) ? implode(', ', $columns) : $columns;
        return $this;
    }

    /**
     * Add where condition
     */
    public function where($column, $operator = null, $value = null, $boolean = 'AND')
    {
        // If only two parameters, assume equals operator
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->where[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $boolean
        ];

        return $this;
    }

    /**
     * Add OR where condition
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Order by clause
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $this->orderBy = "ORDER BY {$column} {$direction}";
        return $this;
    }

    /**
     * Limit clause
     */
    public function limit($limit)
    {
        $this->limit = "LIMIT {$limit}";
        return $this;
    }

    /**
     * Offset clause
     */
    public function offset($offset)
    {
        $this->offset = "OFFSET {$offset}";
        return $this;
    }

    /**
     * Execute SELECT query and return results
     */
    public function get()
    {
        $sql = $this->buildSelectQuery();
        $stmt = $this->executeQuery($sql, $this->params);
        return $stmt->fetchAll();
    }

    /**
     * Execute SELECT query and return first result
     */
    public function first()
    {
        $this->limit(1);
        $sql = $this->buildSelectQuery();
        $stmt = $this->executeQuery($sql, $this->params);
        return $stmt->fetch();
    }

    /**
     * Insert data
     */
    public function insert(array $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        $stmt = $this->executeQuery($sql, $data);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update data
     */
    public function update(array $data)
    {
        $setClause = '';
        foreach ($data as $key => $value) {
            $setClause .= "{$key} = :{$key}, ";
        }
        $setClause = rtrim($setClause, ', ');
        
        $whereClause = $this->buildWhereClause();
        
        $sql = "UPDATE {$this->table} SET {$setClause} {$whereClause}";
        
        $params = array_merge($data, $this->params);
        $stmt = $this->executeQuery($sql, $params);
        
        return $stmt->rowCount();
    }

    /**
     * Delete records
     */
    public function delete()
    {
        $whereClause = $this->buildWhereClause();
        
        $sql = "DELETE FROM {$this->table} {$whereClause}";
        
        $stmt = $this->executeQuery($sql, $this->params);
        return $stmt->rowCount();
    }

    /**
     * Build SELECT query
     */
    protected function buildSelectQuery()
    {
        $whereClause = $this->buildWhereClause();
        
        return "SELECT {$this->select} FROM {$this->table} 
                {$whereClause} 
                {$this->orderBy} 
                {$this->limit} 
                {$this->offset}";
    }

    /**
     * Build WHERE clause
     */
    protected function buildWhereClause()
    {
        if (empty($this->where)) {
            return '';
        }

        $whereClause = 'WHERE ';
        $conditions = [];

        foreach ($this->where as $index => $condition) {
            $paramName = 'where_' . $index . '_' . str_replace('.', '_', $condition['column']);
            
            if ($index === 0) {
                $conditions[] = "{$condition['column']} {$condition['operator']} :{$paramName}";
            } else {
                $conditions[] = "{$condition['boolean']} {$condition['column']} {$condition['operator']} :{$paramName}";
            }
            
            $this->params[$paramName] = $condition['value'];
        }

        $whereClause .= implode(' ', $conditions);
        return $whereClause;
    }

    /**
     * Execute query with parameters
     */
    protected function executeQuery($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new DatabaseException("Query execution failed: " . $e->getMessage());
        }
    }

    /**
     * Reset query builder
     */
    protected function reset()
    {
        $this->select = '*';
        $this->where = [];
        $this->orderBy = '';
        $this->limit = '';
        $this->offset = '';
        $this->params = [];
    }

    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    public function execute(string $sql, array $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Commit transaction
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     *
     */
    public function rollback()
    {
        return $this->pdo->rollBack();
    }

    /**
     * Get PDO instance
     */
    public function getPdo()
    {
        return $this->pdo;
    }
}

