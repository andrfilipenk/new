<?php
// app/Core/Eav/Cache/Driver/CacheDriverInterface.php
namespace Core\Eav\Cache\Driver;

/**
 * Cache Driver Interface
 * 
 * Defines the contract for cache storage drivers
 */
interface CacheDriverInterface
{
    /**
     * Get value from cache
     * 
     * @param string $key
     * @return mixed|null
     */
    public function get(string $key): mixed;

    /**
     * Set value in cache
     * 
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl Time to live in seconds
     * @return bool
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Delete value from cache
     * 
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * Check if key exists
     * 
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Clear all cache
     * 
     * @return bool
     */
    public function clear(): bool;

    /**
     * Check if driver is available
     * 
     * @return bool
     */
    public function isAvailable(): bool;
}
