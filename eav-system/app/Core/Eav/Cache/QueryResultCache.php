<?php

namespace App\Core\Eav\Cache;

use App\Core\Eav\Cache\Driver\CacheDriverInterface;

/**
 * L4 Query Result Cache
 * 
 * Caches query results to avoid expensive database operations.
 * - Caches SELECT query results
 * - Automatic cache key generation from query signature
 * - Tag-based invalidation support
 * - Target hit rate: >50%
 * - Default TTL: 300s (5 minutes)
 * 
 * @package App\Core\Eav\Cache
 */
class QueryResultCache
{
    private CacheDriverInterface $driver;
    private int $defaultTtl;
    private bool $enabled;
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'stores' => 0,
        'invalidations' => 0,
    ];
    private array $tags = [];

    /**
     * @param CacheDriverInterface $driver Cache driver (typically uses L2/L3 driver)
     * @param int $defaultTtl Default TTL in seconds (5 minutes)
     * @param bool $enabled Enable/disable query caching
     */
    public function __construct(
        CacheDriverInterface $driver,
        int $defaultTtl = 300,
        bool $enabled = true
    ) {
        $this->driver = $driver;
        $this->defaultTtl = $defaultTtl;
        $this->enabled = $enabled;
    }

    /**
     * Get cached query result
     * 
     * @param string $signature Query signature (from QuerySignature::generate())
     * @return mixed|null Cached result or null if not found
     */
    public function get(string $signature): mixed
    {
        if (!$this->enabled) {
            return null;
        }

        $cacheKey = $this->makeCacheKey($signature);
        $value = $this->driver->get($cacheKey);
        
        if ($value !== null) {
            $this->stats['hits']++;
        } else {
            $this->stats['misses']++;
        }
        
        return $value;
    }

    /**
     * Store query result in cache
     * 
     * @param string $signature Query signature
     * @param mixed $result Query result to cache
     * @param int|null $ttl TTL in seconds (null = use default)
     * @param array $tags Tags for invalidation
     * @return bool Success status
     */
    public function set(string $signature, mixed $result, ?int $ttl = null, array $tags = []): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $cacheKey = $this->makeCacheKey($signature);
        $success = $this->driver->set($cacheKey, $result, $ttl ?? $this->defaultTtl);
        
        if ($success) {
            $this->stats['stores']++;
            
            // Store tags for invalidation
            if (!empty($tags)) {
                $this->storeTags($cacheKey, $tags);
            }
        }
        
        return $success;
    }

    /**
     * Get or compute query result
     * 
     * @param string $signature Query signature
     * @param callable $callback Callback to execute if cache miss
     * @param int|null $ttl TTL in seconds
     * @param array $tags Tags for invalidation
     * @return mixed Query result
     */
    public function remember(string $signature, callable $callback, ?int $ttl = null, array $tags = []): mixed
    {
        $result = $this->get($signature);
        
        if ($result !== null) {
            return $result;
        }
        
        $result = $callback();
        $this->set($signature, $result, $ttl, $tags);
        
        return $result;
    }

    /**
     * Invalidate specific query
     * 
     * @param string $signature Query signature
     * @return bool Success status
     */
    public function invalidate(string $signature): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $cacheKey = $this->makeCacheKey($signature);
        $success = $this->driver->delete($cacheKey);
        
        if ($success) {
            $this->stats['invalidations']++;
            $this->removeTags($cacheKey);
        }
        
        return $success;
    }

    /**
     * Invalidate queries by tag
     * 
     * @param string $tag Tag to invalidate
     * @return int Number of queries invalidated
     */
    public function invalidateByTag(string $tag): int
    {
        if (!$this->enabled) {
            return 0;
        }

        $tagKey = $this->makeTagKey($tag);
        $cacheKeys = $this->driver->get($tagKey);
        
        if (!is_array($cacheKeys)) {
            return 0;
        }
        
        $invalidated = 0;
        foreach ($cacheKeys as $cacheKey) {
            if ($this->driver->delete($cacheKey)) {
                $invalidated++;
                $this->stats['invalidations']++;
            }
        }
        
        // Remove tag index
        $this->driver->delete($tagKey);
        
        return $invalidated;
    }

    /**
     * Invalidate queries by multiple tags
     * 
     * @param array $tags Tags to invalidate
     * @return int Total number of queries invalidated
     */
    public function invalidateByTags(array $tags): int
    {
        $total = 0;
        
        foreach ($tags as $tag) {
            $total += $this->invalidateByTag($tag);
        }
        
        return $total;
    }

    /**
     * Invalidate queries by entity type
     * 
     * @param string $entityType Entity type code
     * @return int Number of queries invalidated
     */
    public function invalidateByEntityType(string $entityType): int
    {
        return $this->invalidateByTag("entity_type:{$entityType}");
    }

    /**
     * Invalidate queries by entity ID
     * 
     * @param string $entityType Entity type code
     * @param int $entityId Entity ID
     * @return int Number of queries invalidated
     */
    public function invalidateByEntity(string $entityType, int $entityId): int
    {
        return $this->invalidateByTag("entity:{$entityType}:{$entityId}");
    }

    /**
     * Clear all cached queries
     * 
     * @return bool Success status
     */
    public function clear(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        return $this->driver->clear();
    }

    /**
     * Enable query caching
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable query caching
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Check if query caching is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
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
            'enabled' => $this->enabled,
            'driver' => get_class($this->driver),
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
            'stores' => 0,
            'invalidations' => 0,
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
     * Make cache key from query signature
     */
    private function makeCacheKey(string $signature): string
    {
        return 'query_' . $signature;
    }

    /**
     * Make tag index key
     */
    private function makeTagKey(string $tag): string
    {
        return 'tag_' . md5($tag);
    }

    /**
     * Store tags for a cache key
     */
    private function storeTags(string $cacheKey, array $tags): void
    {
        foreach ($tags as $tag) {
            $tagKey = $this->makeTagKey($tag);
            
            // Get existing cache keys for this tag
            $cacheKeys = $this->driver->get($tagKey);
            if (!is_array($cacheKeys)) {
                $cacheKeys = [];
            }
            
            // Add new cache key
            if (!in_array($cacheKey, $cacheKeys, true)) {
                $cacheKeys[] = $cacheKey;
                $this->driver->set($tagKey, $cacheKeys, 86400); // 24 hour TTL for tag index
            }
        }
    }

    /**
     * Remove tags for a cache key
     */
    private function removeTags(string $cacheKey): void
    {
        // This would require reverse index - simplified for now
        // In production, consider using Redis sets for tag management
    }

    /**
     * Warm up cache with pre-computed queries
     * 
     * @param array $queries Array of [signature => result] pairs
     * @param int|null $ttl TTL in seconds
     * @return int Number of queries cached
     */
    public function warmUp(array $queries, ?int $ttl = null): int
    {
        if (!$this->enabled) {
            return 0;
        }

        $cached = 0;
        
        foreach ($queries as $signature => $result) {
            if ($this->set($signature, $result, $ttl)) {
                $cached++;
            }
        }
        
        return $cached;
    }

    /**
     * Get multiple cached query results
     * 
     * @param array $signatures Array of query signatures
     * @return array Associative array of signature => result (missing entries excluded)
     */
    public function getMultiple(array $signatures): array
    {
        if (!$this->enabled || empty($signatures)) {
            return [];
        }

        $results = [];
        
        foreach ($signatures as $signature) {
            $result = $this->get($signature);
            if ($result !== null) {
                $results[$signature] = $result;
            }
        }
        
        return $results;
    }
}
