<?php
// app/Core/Eav/Cache/Driver/ApcuDriver.php
namespace Core\Eav\Cache\Driver;

/**
 * APCu Cache Driver
 * 
 * Uses APCu extension for shared memory caching across PHP processes.
 * Provides best performance when available.
 */
class ApcuDriver implements CacheDriverInterface
{
    private string $prefix;

    public function __construct(string $prefix = 'eav_')
    {
        $this->prefix = $prefix;
    }

    /**
     * Get value from cache
     */
    public function get(string $key): mixed
    {
        $value = apcu_fetch($this->prefix . $key, $success);
        return $success ? $value : null;
    }

    /**
     * Set value in cache
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return apcu_store($this->prefix . $key, $value, $ttl ?? 0);
    }

    /**
     * Delete value from cache
     */
    public function delete(string $key): bool
    {
        return apcu_delete($this->prefix . $key);
    }

    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        return apcu_exists($this->prefix . $key);
    }

    /**
     * Clear all cache with prefix
     */
    public function clear(): bool
    {
        // Clear only keys with our prefix
        $iterator = new \APCUIterator('/^' . preg_quote($this->prefix, '/') . '/');
        return apcu_delete($iterator);
    }

    /**
     * Check if APCu is available
     */
    public function isAvailable(): bool
    {
        return extension_loaded('apcu') && ini_get('apc.enabled');
    }

    /**
     * Get cache info
     */
    public function getInfo(): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        $info = apcu_cache_info(true);
        return [
            'num_entries' => $info['num_entries'] ?? 0,
            'mem_size' => $info['mem_size'] ?? 0,
            'hits' => $info['num_hits'] ?? 0,
            'misses' => $info['num_misses'] ?? 0,
        ];
    }
}
