<?php

namespace App\Core\Eav\Cache;

use App\Core\Eav\Cache\Driver\CacheDriverInterface;
use App\Core\Eav\Cache\Driver\FileDriver;
use App\Core\Eav\Cache\Driver\RedisDriver;

/**
 * L3 Persistent Cache
 * 
 * Provides persistent caching across PHP process restarts.
 * - Default TTL: 3600s (1 hour)
 * - Auto-driver selection: Redis → File
 * - Target hit rate: >60%
 * - Survives process restarts
 * 
 * @package App\Core\Eav\Cache
 */
class PersistentCache
{
    private CacheDriverInterface $driver;
    private int $defaultTtl;
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0,
    ];

    /**
     * @param CacheDriverInterface|null $driver Auto-selects if null (Redis → File)
     * @param int $defaultTtl Default TTL in seconds (1 hour)
     */
    public function __construct(?CacheDriverInterface $driver = null, int $defaultTtl = 3600)
    {
        $this->defaultTtl = $defaultTtl;
        
        if ($driver !== null) {
            $this->driver = $driver;
        } else {
            // Auto-select driver: Redis → File
            $redisDriver = new RedisDriver();
            if ($redisDriver->isAvailable()) {
                $this->driver = $redisDriver;
            } else {
                $this->driver = new FileDriver();
            }
        }
    }

    /**
     * Get value from cache
     */
    public function get(string $key): mixed
    {
        $value = $this->driver->get($key);
        
        if ($value !== null) {
            $this->stats['hits']++;
        } else {
            $this->stats['misses']++;
        }
        
        return $value;
    }

    /**
     * Set value in cache
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $this->stats['sets']++;
        return $this->driver->set($key, $value, $ttl ?? $this->defaultTtl);
    }

    /**
     * Delete value from cache
     */
    public function delete(string $key): bool
    {
        $this->stats['deletes']++;
        return $this->driver->delete($key);
    }

    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool
    {
        return $this->driver->has($key);
    }

    /**
     * Clear all cache entries
     */
    public function clear(): bool
    {
        return $this->driver->clear();
    }

    /**
     * Get value from cache or execute callback and cache result
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }

    /**
     * Get multiple values from cache
     * 
     * @param array $keys Array of cache keys
     * @return array Associative array of key => value (missing keys excluded)
     */
    public function getMultiple(array $keys): array
    {
        $results = [];
        
        foreach ($keys as $key) {
            $value = $this->get($key);
            if ($value !== null) {
                $results[$key] = $value;
            }
        }
        
        return $results;
    }

    /**
     * Set multiple values in cache
     * 
     * @param array $items Associative array of key => value
     * @param int|null $ttl TTL in seconds
     * @return bool True if all items were set successfully
     */
    public function setMultiple(array $items, ?int $ttl = null): bool
    {
        $success = true;
        
        foreach ($items as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Delete multiple values from cache
     * 
     * @param array $keys Array of cache keys
     * @return bool True if all items were deleted successfully
     */
    public function deleteMultiple(array $keys): bool
    {
        $success = true;
        
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }
        
        return $success;
    }

    /**
     * Increment a numeric value
     */
    public function increment(string $key, int $step = 1): int|false
    {
        $value = $this->get($key);
        
        if ($value === null) {
            $value = 0;
        }
        
        if (!is_numeric($value)) {
            return false;
        }
        
        $newValue = (int)$value + $step;
        $this->set($key, $newValue);
        
        return $newValue;
    }

    /**
     * Decrement a numeric value
     */
    public function decrement(string $key, int $step = 1): int|false
    {
        return $this->increment($key, -$step);
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $total = $this->stats['hits'] + $this->stats['misses'];
        $hitRate = $total > 0 ? ($this->stats['hits'] / $total) * 100 : 0;
        
        return array_merge($this->stats, [
            'total_requests' => $total,
            'hit_rate' => round($hitRate, 2),
            'driver' => get_class($this->driver),
            'driver_available' => $this->driver->isAvailable(),
        ]);
    }

    /**
     * Reset cache statistics
     */
    public function resetStats(): void
    {
        $this->stats = [
            'hits' => 0,
            'misses' => 0,
            'sets' => 0,
            'deletes' => 0,
        ];
    }

    /**
     * Get the underlying cache driver
     */
    public function getDriver(): CacheDriverInterface
    {
        return $this->driver;
    }

    /**
     * Check if cache is available
     */
    public function isAvailable(): bool
    {
        return $this->driver->isAvailable();
    }
}
