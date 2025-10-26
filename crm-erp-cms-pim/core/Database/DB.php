<?php

namespace Core\Database;

/**
 * Database Facade
 * 
 * Provides static access to database connection
 */
class DB
{
    static protected $inTransaction = false;
    /**
     * Get connection instance
     * 
     * @param string $name
     * @return Connection
     * 
     */
    public static function connection(string $name = 'default'): Connection
    {
        return Connection::getInstance($name);
    }

    /**
     * Execute a select query
     * 
     * @param string $query
     * @param array $bindings
     * @return array
     */
    public static function select(string $query, array $bindings = []): array
    {
        return static::connection()->select($query, $bindings);
    }

    /**
     * Execute a select query and return first row
     * 
     * @param string $query
     * @param array $bindings
     * @return array|null
     */
    public static function selectOne(string $query, array $bindings = []): ?array
    {
        return static::connection()->selectOne($query, $bindings);
    }

    /**
     * Execute an insert query
     * 
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public static function insert(string $query, array $bindings = []): int
    {
        return static::connection()->insert($query, $bindings);
    }

    /**
     * Execute an update query
     * 
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public static function update(string $query, array $bindings = []): int
    {
        return static::connection()->update($query, $bindings);
    }

    /**
     * Execute a delete query
     * 
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public static function delete(string $query, array $bindings = []): int
    {
        return static::connection()->delete($query, $bindings);
    }

    /**
     * Execute a statement
     * 
     * @param string $query
     * @param array $bindings
     * @return bool
     */
    public static function statement(string $query, array $bindings = []): bool
    {
        return static::connection()->statement($query, $bindings);
    }

    /**
     * Begin a transaction
     * 
     * @return void
     */
    public static function beginTransaction(): void
    {
        if (!self::$inTransaction) {
            static::connection()->beginTransaction();
        }
    }

    /**
     * Commit a transaction
     * 
     * @return void
     */
    public static function commit(): void
    {
        if (self::$inTransaction) {
            static::connection()->commit();
        }
    }

    /**
     * Rollback a transaction
     * 
     * @return void
     */
    public static function rollback(): void
    {
        if (self::$inTransaction) {
            static::connection()->rollback();
        }
    }

    /**
     * Execute callback within transaction
     * 
     * @param callable $callback
     * @return mixed
     */
    public static function transaction(callable $callback): mixed
    {
        self::$inTransaction = true;
        return static::connection()->transaction($callback);
    }

    /**
     * Get Query Builder instance for table
     * 
     * @param string $table
     * @return QueryBuilder
     */
    public static function table(string $table): QueryBuilder
    {
        $builder = new QueryBuilder(static::connection());
        return $builder->table($table);
    }
}
