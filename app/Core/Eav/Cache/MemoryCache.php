<?php
// app/Core/Eav/Cache/MemoryCache.php
namespace Core\Eav\Cache;

use Core\Eav\Cache\Driver\CacheDriverInterface;
use Core\Eav\Cache\Driver\ApcuDriver;
use Core\Eav\Cache\Driver\StaticDriver;

/**
 * L2: Memory Cache
 * 
 * Application-level memory cache that persists across requests.
 * Uses APCu if available, falls back to static arrays.
 */
class MemoryCache
{
    private CacheDriverInterface $driver;
    private int $defaultTtl;
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
    ];

    public function __construct(?CacheDriverInterface $driver = null, int $defaultTtl = 900)
    {
        $this->defaultTtl = $defaultTtl;
        
        if ($driver !== null) {
            $this->driver = $driver;
        } else {
            // Auto-select driver
            $apcuDriver = new ApcuDriver();
            if ($apcuDriver->isAvailable()) {
                $this->driver = $apcuDriver;
            } else {
                $this->driver = new StaticDriver();
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
        return $this->driver->delete($key);
    }

    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        return $this->driver->has($key);
    }

    /**
     * Clear all cache
     */
    public function clear(): bool
    {
        return $this->driver->clear();
    }

    /**
     * Get or set (lazy loading pattern)
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
     * Get cache statistics
     */
    public function getStats(): array
    {
        $total = $this->stats['hits'] + $this->stats['misses'];
        $hitRate = $total > 0 ? ($this->stats['hits'] / $total) * 100 : 0;
        
        return [
            'hits' => $this->stats['hits'],
            'misses' => $this->stats['misses'],
            'sets' => $this->stats['sets'],
            'hit_rate' => round($hitRate, 2),
            'driver' => get_class($this->driver),
        ];
    }

    /**
     * Get underlying driver
     */
    public function getDriver(): CacheDriverInterface
    {
        return $this->driver;
    }

    /**
     * Check if driver is available
     */
    public function isAvailable(): bool
    {
        return $this->driver->isAvailable();
    }
}
