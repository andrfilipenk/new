<?php

namespace App\Core\Eav\Cache\Driver;

use Redis;
use RedisException;

/**
 * Redis Cache Driver
 * 
 * Provides high-performance persistent caching using Redis.
 * - Ultra-fast in-memory storage
 * - Native TTL support
 * - Atomic operations
 * - Network-based shared cache
 * 
 * @package App\Core\Eav\Cache\Driver
 */
class RedisDriver implements CacheDriverInterface
{
    private ?Redis $redis = null;
    private string $prefix;
    private string $host;
    private int $port;
    private ?string $password;
    private int $database;
    private float $timeout;
    private bool $connected = false;

    /**
     * @param string $host Redis server host
     * @param int $port Redis server port
     * @param string|null $password Redis password (null if no auth)
     * @param int $database Redis database number (0-15)
     * @param string $prefix Key prefix for namespacing
     * @param float $timeout Connection timeout in seconds
     */
    public function __construct(
        string $host = '127.0.0.1',
        int $port = 6379,
        ?string $password = null,
        int $database = 0,
        string $prefix = 'eav_l3_',
        float $timeout = 2.0
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->database = $database;
        $this->prefix = $prefix;
        $this->timeout = $timeout;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): mixed
    {
        if (!$this->connect()) {
            return null;
        }

        try {
            $value = $this->redis->get($this->prefix . $key);
            
            if ($value === false) {
                return null;
            }
            
            return unserialize($value);
        } catch (RedisException $e) {
            $this->handleException($e);
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        if (!$this->connect()) {
            return false;
        }

        try {
            $serialized = serialize($value);
            return $this->redis->setex($this->prefix . $key, $ttl, $serialized);
        } catch (RedisException $e) {
            $this->handleException($e);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        if (!$this->connect()) {
            return false;
        }

        try {
            $this->redis->del($this->prefix . $key);
            return true;
        } catch (RedisException $e) {
            $this->handleException($e);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        if (!$this->connect()) {
            return false;
        }

        try {
            return (bool)$this->redis->exists($this->prefix . $key);
        } catch (RedisException $e) {
            $this->handleException($e);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        if (!$this->connect()) {
            return false;
        }

        try {
            // Delete all keys matching prefix pattern
            $pattern = $this->prefix . '*';
            $keys = $this->redis->keys($pattern);
            
            if (empty($keys)) {
                return true;
            }
            
            $this->redis->del($keys);
            return true;
        } catch (RedisException $e) {
            $this->handleException($e);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        if (!extension_loaded('redis')) {
            return false;
        }

        return $this->connect();
    }

    /**
     * Increment a numeric value
     * 
     * @param string $key Cache key
     * @param int $step Increment step
     * @return int|false New value or false on failure
     */
    public function increment(string $key, int $step = 1): int|false
    {
        if (!$this->connect()) {
            return false;
        }

        try {
            return $this->redis->incrBy($this->prefix . $key, $step);
        } catch (RedisException $e) {
            $this->handleException($e);
            return false;
        }
    }

    /**
     * Decrement a numeric value
     * 
     * @param string $key Cache key
     * @param int $step Decrement step
     * @return int|false New value or false on failure
     */
    public function decrement(string $key, int $step = 1): int|false
    {
        if (!$this->connect()) {
            return false;
        }

        try {
            return $this->redis->decrBy($this->prefix . $key, $step);
        } catch (RedisException $e) {
            $this->handleException($e);
            return false;
        }
    }

    /**
     * Get cache statistics from Redis
     * 
     * @return array Redis server statistics
     */
    public function getInfo(): array
    {
        if (!$this->connect()) {
            return [];
        }

        try {
            $info = $this->redis->info();
            
            // Count keys with our prefix
            $pattern = $this->prefix . '*';
            $keys = $this->redis->keys($pattern);
            $keyCount = count($keys);
            
            return [
                'redis_version' => $info['redis_version'] ?? 'unknown',
                'used_memory' => $info['used_memory'] ?? 0,
                'used_memory_human' => $info['used_memory_human'] ?? '0',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands_processed' => $info['total_commands_processed'] ?? 0,
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'uptime_in_seconds' => $info['uptime_in_seconds'] ?? 0,
                'keys_with_prefix' => $keyCount,
            ];
        } catch (RedisException $e) {
            $this->handleException($e);
            return [];
        }
    }

    /**
     * Get TTL for a key
     * 
     * @param string $key Cache key
     * @return int|false TTL in seconds, -1 if no expiry, -2 if not exists, false on error
     */
    public function getTtl(string $key): int|false
    {
        if (!$this->connect()) {
            return false;
        }

        try {
            return $this->redis->ttl($this->prefix . $key);
        } catch (RedisException $e) {
            $this->handleException($e);
            return false;
        }
    }

    /**
     * Extend TTL for a key
     * 
     * @param string $key Cache key
     * @param int $ttl New TTL in seconds
     * @return bool Success status
     */
    public function expire(string $key, int $ttl): bool
    {
        if (!$this->connect()) {
            return false;
        }

        try {
            return (bool)$this->redis->expire($this->prefix . $key, $ttl);
        } catch (RedisException $e) {
            $this->handleException($e);
            return false;
        }
    }

    /**
     * Get multiple values in one operation
     * 
     * @param array $keys Array of cache keys
     * @return array Associative array of key => value
     */
    public function getMultiple(array $keys): array
    {
        if (!$this->connect() || empty($keys)) {
            return [];
        }

        try {
            $prefixedKeys = array_map(fn($k) => $this->prefix . $k, $keys);
            $values = $this->redis->mGet($prefixedKeys);
            
            $result = [];
            foreach ($keys as $i => $key) {
                if ($values[$i] !== false) {
                    $result[$key] = unserialize($values[$i]);
                }
            }
            
            return $result;
        } catch (RedisException $e) {
            $this->handleException($e);
            return [];
        }
    }

    /**
     * Set multiple values in one operation
     * 
     * @param array $items Associative array of key => value
     * @param int $ttl TTL in seconds
     * @return bool Success status
     */
    public function setMultiple(array $items, int $ttl = 3600): bool
    {
        if (!$this->connect() || empty($items)) {
            return false;
        }

        try {
            $pipeline = $this->redis->multi(Redis::PIPELINE);
            
            foreach ($items as $key => $value) {
                $serialized = serialize($value);
                $pipeline->setex($this->prefix . $key, $ttl, $serialized);
            }
            
            $pipeline->exec();
            return true;
        } catch (RedisException $e) {
            $this->handleException($e);
            return false;
        }
    }

    /**
     * Connect to Redis server
     */
    private function connect(): bool
    {
        if ($this->connected && $this->redis !== null) {
            try {
                // Test connection
                $this->redis->ping();
                return true;
            } catch (RedisException $e) {
                $this->connected = false;
            }
        }

        if (!extension_loaded('redis')) {
            return false;
        }

        try {
            $this->redis = new Redis();
            
            if (!$this->redis->connect($this->host, $this->port, $this->timeout)) {
                return false;
            }
            
            if ($this->password !== null) {
                if (!$this->redis->auth($this->password)) {
                    return false;
                }
            }
            
            if (!$this->redis->select($this->database)) {
                return false;
            }
            
            $this->connected = true;
            return true;
        } catch (RedisException $e) {
            $this->handleException($e);
            return false;
        }
    }

    /**
     * Handle Redis exceptions
     */
    private function handleException(RedisException $e): void
    {
        $this->connected = false;
        // Log error in production: error_log('Redis error: ' . $e->getMessage());
    }

    /**
     * Close Redis connection
     */
    public function disconnect(): void
    {
        if ($this->redis !== null && $this->connected) {
            try {
                $this->redis->close();
            } catch (RedisException $e) {
                // Ignore close errors
            }
            $this->connected = false;
        }
    }

    /**
     * Destructor - close connection
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
