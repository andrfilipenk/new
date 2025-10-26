<?php

namespace Core\Database;

use PDO;
use PDOException;
use Core\Config\Config;

/**
 * Database Connection Manager
 * 
 * Manages database connections using Singleton pattern with lazy initialization
 */
class Connection
{
    /**
     * Connection instances
     * 
     * @var array
     */
    protected static array $instances = [];

    /**
     * PDO instance
     * 
     * @var PDO|null
     */
    protected ?PDO $pdo = null;

    /**
     * Connection name
     * 
     * @var string
     */
    protected string $name;

    /**
     * Connection configuration
     * 
     * @var array
     */
    protected array $config;

    /**
     * Transaction level
     * 
     * @var int
     */
    protected int $transactionLevel = 0;

    /**
     * Private constructor to prevent direct instantiation
     * 
     * @param string $name
     * @param array $config
     */
    private function __construct(string $name, array $config)
    {
        $this->name = $name;
        $this->config = $config;
    }

    /**
     * Get connection instance
     * 
     * @param string $name
     * @return static
     */
    public static function getInstance(string $name = 'default'): static
    {
        if (!isset(self::$instances[$name])) {
            $configKey = $name === 'default' 
                ? Config::get('database.default', 'mysql')
                : $name;
            
            $config = Config::get("database.connections.{$configKey}");
            
            if (!$config) {
                throw new \RuntimeException("Database connection [{$name}] not configured.");
            }

            self::$instances[$name] = new static($name, $config);
        }

        return self::$instances[$name];
    }

    /**
     * Get PDO instance
     * 
     * @return PDO
     */
    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }

        return $this->pdo;
    }

    /**
     * Establish database connection
     * 
     * @return void
     */
    protected function connect(): void
    {
        $driver = $this->config['driver'] ?? 'mysql';
        $host = $this->config['host'] ?? '127.0.0.1';
        $port = $this->config['port'] ?? '3306';
        $database = $this->config['database'] ?? '';
        $username = $this->config['username'] ?? 'root';
        $password = $this->config['password'] ?? '';
        $charset = $this->config['charset'] ?? 'utf8mb4';
        $collation = $this->config['collation'] ?? 'utf8mb4_unicode_ci';

        $dsn = "{$driver}:host={$host};port={$port};dbname={$database};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$collation}",
        ];

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Could not connect to database [{$this->name}]: " . $e->getMessage()
            );
        }
    }

    /**
     * Reconnect to database
     * 
     * @return void
     */
    public function reconnect(): void
    {
        $this->disconnect();
        $this->connect();
    }

    /**
     * Disconnect from database
     * 
     * @return void
     */
    public function disconnect(): void
    {
        $this->pdo = null;
    }

    /**
     * Execute a query
     * 
     * @param string $query
     * @param array $bindings
     * @return \PDOStatement
     */
    public function query(string $query, array $bindings = []): \PDOStatement
    {
        $pdo = $this->getPdo();
        
        try {
            $statement = $pdo->prepare($query);
            $statement->execute($bindings);
            return $statement;
        } catch (PDOException $e) {
            throw new \RuntimeException(
                "Database query failed: " . $e->getMessage() . "\nQuery: {$query}\nBindings: " . print_r($bindings, true)
            );
        }
    }

    /**
     * Execute a select query
     * 
     * @param string $query
     * @param array $bindings
     * @return array
     */
    public function select(string $query, array $bindings = []): array
    {
        $statement = $this->query($query, $bindings);
        return $statement->fetchAll();
    }

    /**
     * Execute a select query and return first row
     * 
     * @param string $query
     * @param array $bindings
     * @return array|null
     */
    public function selectOne(string $query, array $bindings = []): ?array
    {
        $statement = $this->query($query, $bindings);
        $result = $statement->fetch();
        return $result ?: null;
    }

    /**
     * Execute an insert query
     * 
     * @param string $query
     * @param array $bindings
     * @return int Last insert ID
     */
    public function insert(string $query, array $bindings = []): int
    {
        $this->query($query, $bindings);
        return (int) $this->getPdo()->lastInsertId();
    }

    /**
     * Execute an update query
     * 
     * @param string $query
     * @param array $bindings
     * @return int Affected rows
     */
    public function update(string $query, array $bindings = []): int
    {
        $statement = $this->query($query, $bindings);
        return $statement->rowCount();
    }

    /**
     * Execute a delete query
     * 
     * @param string $query
     * @param array $bindings
     * @return int Affected rows
     */
    public function delete(string $query, array $bindings = []): int
    {
        $statement = $this->query($query, $bindings);
        return $statement->rowCount();
    }

    /**
     * Execute a statement
     * 
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public function statement(string $query, array $bindings = []): bool
    {
        $statement = $this->query($query, $bindings);
        return $statement->rowCount() > 0;
    }

    /**
     * Begin a transaction
     * 
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->transactionLevel++;

        if ($this->transactionLevel === 1) {
            $this->getPdo()->beginTransaction();
        }
    }

    /**
     * Commit a transaction
     * 
     * @return void
     */
    public function commit(): void
    {
        if ($this->transactionLevel === 1) {
            $this->getPdo()->commit();
        }

        $this->transactionLevel = max(0, $this->transactionLevel - 1);
    }

    /**
     * Rollback a transaction
     * 
     * @return void
     */
    public function rollback(): void
    {
        if ($this->transactionLevel === 1) {
            if ($this->getPdo()->inTransaction()) {
                $this->getPdo()->rollBack();
            }
        }

        $this->transactionLevel = max(0, $this->transactionLevel - 1);
    }

    /**
     * Execute callback within transaction
     * 
     * @param callable $callback
     * @return mixed
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Get table prefix
     * 
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->config['prefix'] ?? '';
    }

    /**
     * Get connection name
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Check if connected
     * 
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup()
    {
        throw new \RuntimeException("Cannot unserialize singleton");
    }
}
