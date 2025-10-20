<?php
// app/Core/Eav/Cache/Driver/StaticDriver.php
namespace Core\Eav\Cache\Driver;

/**
 * Static Array Cache Driver
 * 
 * Stores cache in static array - persists across instances but not requests.
 * Fallback when APCu is not available.
 */
class StaticDriver implements CacheDriverInterface
{
    private static array $cache = [];
    private static array $expiry = [];

    /**
     * Get value from cache
     */
    public function get(string $key): mixed
    {
        // Check if exists and not expired
        if (!isset(self::$cache[$key])) {
            return null;
        }

        if (isset(self::$expiry[$key]) && self::$expiry[$key] < time()) {
            unset(self::$cache[$key], self::$expiry[$key]);
            return null;
        }

        return self::$cache[$key];
    }

    /**
     * Set value in cache
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        self::$cache[$key] = $value;
        
        if ($ttl !== null) {
            self::$expiry[$key] = time() + $ttl;
        }

        return true;
    }

    /**
     * Delete value from cache
     */
    public function delete(string $key): bool
    {
        unset(self::$cache[$key], self::$expiry[$key]);
        return true;
    }

    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        if (!isset(self::$cache[$key])) {
            return false;
        }

        if (isset(self::$expiry[$key]) && self::$expiry[$key] < time()) {
            unset(self::$cache[$key], self::$expiry[$key]);
            return false;
        }

        return true;
    }

    /**
     * Clear all cache
     */
    public function clear(): bool
    {
        self::$cache = [];
        self::$expiry = [];
        return true;
    }

    /**
     * Check if driver is available
     */
    public function isAvailable(): bool
    {
        return true; // Always available
    }

    /**
     * Get cache size
     */
    public function getSize(): int
    {
        return count(self::$cache);
    }

    /**
     * Clean expired entries
     */
    public function cleanExpired(): int
    {
        $cleaned = 0;
        $now = time();

        foreach (self::$expiry as $key => $expiry) {
            if ($expiry < $now) {
                unset(self::$cache[$key], self::$expiry[$key]);
                $cleaned++;
            }
        }

        return $cleaned;
    }
}
