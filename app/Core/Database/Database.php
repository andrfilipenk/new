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
    protected $table;

    // Query parts
    protected $selects = ['*'];
    protected $wheres = [];
    protected $bindings = [];
    protected $orders = [];
    protected $limit;
    protected $offset;

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
                PDO::ATTR_PERSISTENT => $cfg['persistent'] ?? false,
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
    public function select($columns = '*')
    {
        $this->selects = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Add where condition
     */
    public function where($column, $operator = null, $value = null, $boolean = 'AND')
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = compact('column', 'operator', 'value', 'boolean');
        
        if (!is_null($value)) {
            $this->bindings[] = $value;
        }

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
     * Add a WHERE IN condition
     */
    public function whereIn($column, array $values, $boolean = 'AND')
    {
        $this->wheres[] = ['column' => $column, 'operator' => 'IN', 'values' => $values, 'boolean' => $boolean];
        $this->bindings = array_merge($this->bindings, $values);
        return $this;
    }

    /**
     * Order by clause
     */
    public function orderBy($column, $direction = 'ASC')
    {
        $this->orders[] = "{$column} " . strtoupper($direction);
        return $this;
    }

    /**
     * Limit clause
     */
    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    /**
     * Offset clause
     */
    public function offset($offset)
    {
        $this->offset = (int) $offset;
        return $this;
    }

    /**
     * Execute SELECT query and return results
     */
    public function get()
    {
        $sql = $this->buildSelectQuery();
        $stmt = $this->executeQuery($sql, $this->bindings);
        return $stmt->fetchAll();
    }

    /**
     * Execute SELECT query and return first result
     */
    public function first()
    {
        $this->limit(1);
        $sql = $this->buildSelectQuery();
        $stmt = $this->executeQuery($sql, $this->bindings);
        return $stmt->fetch();
    }

    /**
     * Insert data
     */
    public function insert(array $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = rtrim(str_repeat('?,', count($data)), ',');
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        $this->executeQuery($sql, array_values($data));
        return $this->pdo->lastInsertId();
    }

    /**
     * Update data
     */
    public function update(array $data)
    {
        $setClause = implode(' = ?, ', array_keys($data)) . ' = ?';
        
        $whereClause = $this->buildWhereClause();
        
        $sql = "UPDATE {$this->table} SET {$setClause} {$whereClause}";
        
        $params = array_merge(array_values($data), $this->bindings);
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
        
        $stmt = $this->executeQuery($sql, $this->bindings);
        return $stmt->rowCount();
    }

    /**
     * Build SELECT query
     */
    protected function buildSelectQuery()
    {
        $sql = "SELECT " . implode(', ', $this->selects) . " FROM {$this->table}";
        
        $sql .= $this->buildWhereClause();

        if (!empty($this->orders)) {
            $sql .= " ORDER BY " . implode(', ', $this->orders);
        }
        if (!is_null($this->limit)) {
            $sql .= " LIMIT {$this->limit}";
        }
        if (!is_null($this->offset)) {
            $sql .= " OFFSET {$this->offset}";
        }
        
        return $sql;
    }

    /**
     * Build WHERE clause
     */
    protected function buildWhereClause()
    {
        if (empty($this->wheres)) {
            return '';
        }

        $conditions = [];
        foreach ($this->wheres as $i => $condition) {
            $prefix = ($i > 0) ? "{$condition['boolean']} " : '';

            if ($condition['operator'] === 'IN') {
                $placeholders = rtrim(str_repeat('?,', count($condition['values'])), ',');
                $conditions[] = "{$prefix}{$condition['column']} IN ({$placeholders})";
            } else {
                $conditions[] = "{$prefix}{$condition['column']} {$condition['operator']} ?";
            }
        }

        return " WHERE " . implode(' ', $conditions);
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
            throw new DatabaseException("Query execution failed: " . $e->getMessage() . " (SQL: $sql)");
        }
    }

    /**
     * Reset query builder
     */
    protected function reset()
    {
        $this->selects = ['*'];
        $this->wheres = [];
        $this->bindings = [];
        $this->orders = [];
        $this->limit = null;
        $this->offset = null;
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
        $stmt->execute($params);
        return $stmt;
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

// Example usage:
// $db = new Database();
// $users = $db->table('users')->where('status', 'active')->orderBy('created_at', 'DESC')->get();
// foreach ($users as $user) {
//     echo $user->name . "\n";
// }

// class User extends Model {
 //     protected $table = ?users?;
 //     public function roles() {
 //         return $this-?belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
 //     }
 // }
 
 // class Role extends Model {
 //     protected $table = ?roles?;
 //     public function users() {
 //         return $this-?belongsToMany(User::class, 'user_roles', 'role_id', 'user_id');
 //     }
 // }
 
 // class Post extends Model {
 //     protected $table = ?posts?;
 //     public function user() {
//         return $this-?belongsTo(User::class);
 //     }
 // }

// class Profile extends Model {
    //     protected $table = ?profiles?;
    //     public function user() {
    //         return $this-?belongsTo(User::class);
    //     }
    // }

// Example usage:
// $user = (new User())->find(1);
 // echo $user->name;
 // $profile = $user->profile();
 // echo $profile->bio;
 // $posts = $user->posts();
 // foreach ($posts as $post) {
 //     echo $post->title . "\n";
 // }
 
 // $roles = $user->roles();
 // foreach ($roles as $role) {
 //     echo $role->name . "\n";
 // }
// $role = (new Role())->find(1);
 // echo $role->name;
// $users = $role->users();
 // foreach ($users as $user) {
 //     echo $user->name . "\n";
 // }
// class User extends Model {
 //     protected $table = ?users?;
//     public function profile() {  
 //         return $this-?hasOne(Profile::class);
 //     }
 
 //     public function posts() {
 //         return $this-?hasMany(Post::class);
 //     }
 
 //     public function roles() {
 //         return $this-?belongsToMany(Role::class);
 //     }
 // }
 
 // class Profile extends Model {
 //     protected $table = ?profiles?;
 //     public function user() {
 //         return $this-?belongsTo(User::class);
 //     }
 // }
 
 // class Post extends Model {
 //     protected $table = ?posts?;
 //     public function user() {
//         return $this-?belongsTo(User::class);
 //     }
 // }
 
 // class Role extends Model {
 //     protected $table = ?roles?;
 //     public function users() {
 //         return $this-?belongsToMany(User::class);
 //     }
 // }
