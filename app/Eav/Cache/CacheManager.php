<?php
// app/Eav/Cache/CacheManager.php
namespace Eav\Cache;

use Core\Database\Database;

/**
 * Cache Manager
 * 
 * Multi-level caching (query cache, schema cache, entity cache) with invalidation
 */
class CacheManager
{
    private Database $db;
    private array $memoryCache = [];
    private string $cachePrefix = 'eav:';
    private int $defaultTtl = 3600;

    public function __construct(Database $db, string $cachePrefix = 'eav:', int $defaultTtl = 3600)
    {
        $this->db = $db;
        $this->cachePrefix = $cachePrefix;
        $this->defaultTtl = $defaultTtl;
    }

    /**
     * Get a cached value
     */
    public function get(string $key): mixed
    {
        $fullKey = $this->cachePrefix . $key;

        // Check memory cache first
        if (isset($this->memoryCache[$fullKey])) {
            return $this->memoryCache[$fullKey];
        }

        // Check database cache
        $result = $this->db->table('eav_entity_cache')
            ->where('cache_key', $fullKey)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->first();

        if ($result) {
            $value = $this->unserialize($result['cache_value']);
            $this->memoryCache[$fullKey] = $value;
            return $value;
        }

        return null;
    }

    /**
     * Set a cached value
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $fullKey = $this->cachePrefix . $key;
        $ttl = $ttl ?? $this->defaultTtl;
        $expiresAt = date('Y-m-d H:i:s', time() + $ttl);

        // Set in memory cache
        $this->memoryCache[$fullKey] = $value;

        // Set in database cache
        $serialized = $this->serialize($value);

        // Check if exists
        $existing = $this->db->table('eav_entity_cache')
            ->where('cache_key', $fullKey)
            ->first();

        if ($existing) {
            $affected = $this->db->table('eav_entity_cache')
                ->where('cache_key', $fullKey)
                ->update([
                    'cache_value' => $serialized,
                    'ttl' => $ttl,
                    'expires_at' => $expiresAt,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            return $affected > 0;
        } else {
            $id = $this->db->table('eav_entity_cache')->insert([
                'cache_key' => $fullKey,
                'cache_value' => $serialized,
                'ttl' => $ttl,
                'expires_at' => $expiresAt,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            return $id > 0;
        }
    }

    /**
     * Delete a cached value
     */
    public function delete(string $key): bool
    {
        $fullKey = $this->cachePrefix . $key;

        // Remove from memory cache
        unset($this->memoryCache[$fullKey]);

        // Remove from database cache
        $affected = $this->db->table('eav_entity_cache')
            ->where('cache_key', $fullKey)
            ->delete();

        return $affected > 0;
    }

    /**
     * Check if a key exists in cache
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Clear all cache or by pattern
     */
    public function clear(?string $pattern = null): bool
    {
        if ($pattern === null) {
            // Clear all
            $this->memoryCache = [];
            $affected = $this->db->table('eav_entity_cache')->delete();
            return $affected > 0;
        } else {
            // Clear by pattern
            $fullPattern = $this->cachePrefix . $pattern;
            
            // Clear memory cache
            foreach (array_keys($this->memoryCache) as $key) {
                if (fnmatch($fullPattern, $key)) {
                    unset($this->memoryCache[$key]);
                }
            }

            // Clear database cache (using LIKE)
            $likePattern = str_replace('*', '%', $fullPattern);
            $affected = $this->db->table('eav_entity_cache')
                ->whereRaw('cache_key LIKE ?', [$likePattern])
                ->delete();

            return $affected > 0;
        }
    }

    /**
     * Get or set cache value
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
     * Invalidate entity cache
     */
    public function invalidateEntity(int $entityId): void
    {
        $this->clear("entity:{$entityId}:*");
        $this->delete("entity:{$entityId}");
    }

    /**
     * Invalidate entity type cache
     */
    public function invalidateEntityType(int $entityTypeId): void
    {
        $this->clear("entity_type:{$entityTypeId}:*");
        $this->delete("entity_type:{$entityTypeId}");
    }

    /**
     * Invalidate query cache
     */
    public function invalidateQuery(string $queryHash): void
    {
        $this->delete("query:{$queryHash}");
    }

    /**
     * Invalidate all queries for an entity type
     */
    public function invalidateEntityTypeQueries(int $entityTypeId): void
    {
        $this->clear("query:type:{$entityTypeId}:*");
    }

    /**
     * Clean expired cache entries
     */
    public function cleanExpired(): int
    {
        $affected = $this->db->table('eav_entity_cache')
            ->where('expires_at', '<', date('Y-m-d H:i:s'))
            ->delete();

        return $affected;
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $total = $this->db->table('eav_entity_cache')
            ->selectRaw('COUNT(*) as count')
            ->first();

        $expired = $this->db->table('eav_entity_cache')
            ->where('expires_at', '<', date('Y-m-d H:i:s'))
            ->selectRaw('COUNT(*) as count')
            ->first();

        $size = $this->db->table('eav_entity_cache')
            ->selectRaw('SUM(LENGTH(cache_value)) as size')
            ->first();

        return [
            'total_entries' => $total['count'] ?? 0,
            'expired_entries' => $expired['count'] ?? 0,
            'active_entries' => ($total['count'] ?? 0) - ($expired['count'] ?? 0),
            'memory_cache_size' => count($this->memoryCache),
            'database_cache_size' => $size['size'] ?? 0,
        ];
    }

    /**
     * Warm up cache for entity type
     */
    public function warmUpEntityType(int $entityTypeId, array $attributes): void
    {
        // This would be called to pre-populate cache with frequently accessed data
        $this->set("entity_type:{$entityTypeId}:attributes", $attributes, 7200);
    }

    /**
     * Serialize value for storage
     */
    private function serialize(mixed $value): string
    {
        return json_encode($value);
    }

    /**
     * Unserialize value from storage
     */
    private function unserialize(string $value): mixed
    {
        return json_decode($value, true);
    }

    /**
     * Get memory cache for debugging
     */
    public function getMemoryCache(): array
    {
        return $this->memoryCache;
    }

    /**
     * Clear only memory cache
     */
    public function clearMemoryCache(): void
    {
        $this->memoryCache = [];
    }
}
