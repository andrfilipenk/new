<?php

namespace App\Core\Eav\Cache;

use App\Core\Eav\Cache\RequestCache;
use App\Core\Eav\Cache\MemoryCache;
use App\Core\Eav\Cache\PersistentCache;
use App\Core\Eav\Cache\QueryResultCache;

/**
 * Cache Manager
 * 
 * Orchestrates all cache levels (L1-L4) with intelligent fallback.
 * - Unified cache interface
 * - Multi-level cache hierarchy
 * - Automatic cache warming
 * - Comprehensive statistics
 * 
 * Cache Hierarchy:
 * L1 (Request) → L2 (Memory/APCu) → L3 (Persistent/File/Redis) → L4 (Query Results)
 * 
 * @package App\Core\Eav\Cache
 */
class CacheManager
{
    private RequestCache $l1Cache;
    private MemoryCache $l2Cache;
    private PersistentCache $l3Cache;
    private QueryResultCache $l4Cache;
    
    private array $config;
    private array $stats = [
        'l1_hits' => 0,
        'l2_hits' => 0,
        'l3_hits' => 0,
        'l4_hits' => 0,
        'total_requests' => 0,
        'cache_sets' => 0,
        'invalidations' => 0,
    ];

    /**
     * @param array $config Cache configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'l1_enable' => true,
            'l2_enable' => true,
            'l3_enable' => true,
            'l4_enable' => true,
            'l1_ttl' => 0,      // Request lifetime
            'l2_ttl' => 900,    // 15 minutes
            'l3_ttl' => 3600,   // 1 hour
            'l4_ttl' => 300,    // 5 minutes
            'auto_warm' => false,
        ], $config);

        $this->initializeCaches();
    }

    /**
     * Get value from cache (multi-level lookup)
     * 
     * @param string $key Cache key
     * @param string|null $level Specific cache level (L1/L2/L3/L4) or null for auto
     * @return mixed|null Cached value or null
     */
    public function get(string $key, ?string $level = null): mixed
    {
        $this->stats['total_requests']++;

        // Specific level lookup
        if ($level !== null) {
            return $this->getFromLevel($key, $level);
        }

        // Multi-level lookup with backfill
        // L1: Request Cache
        if ($this->config['l1_enable']) {
            $value = $this->l1Cache->get($key);
            if ($value !== null) {
                $this->stats['l1_hits']++;
                return $value;
            }
        }

        // L2: Memory Cache (APCu/Static)
        if ($this->config['l2_enable']) {
            $value = $this->l2Cache->get($key);
            if ($value !== null) {
                $this->stats['l2_hits']++;
                
                // Backfill L1
                if ($this->config['l1_enable']) {
                    $this->l1Cache->set($key, $value);
                }
                
                return $value;
            }
        }

        // L3: Persistent Cache (File/Redis)
        if ($this->config['l3_enable']) {
            $value = $this->l3Cache->get($key);
            if ($value !== null) {
                $this->stats['l3_hits']++;
                
                // Backfill L2 and L1
                if ($this->config['l2_enable']) {
                    $this->l2Cache->set($key, $value, $this->config['l2_ttl']);
                }
                if ($this->config['l1_enable']) {
                    $this->l1Cache->set($key, $value);
                }
                
                return $value;
            }
        }

        return null;
    }

    /**
     * Set value in cache (all levels)
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl TTL in seconds (null = use defaults)
     * @param array $levels Specific levels to set (empty = all enabled)
     * @return bool Success status
     */
    public function set(string $key, mixed $value, ?int $ttl = null, array $levels = []): bool
    {
        $this->stats['cache_sets']++;
        $success = true;

        $targetLevels = empty($levels) ? ['L1', 'L2', 'L3'] : $levels;

        foreach ($targetLevels as $level) {
            $levelSuccess = match($level) {
                'L1' => $this->config['l1_enable'] && $this->l1Cache->set($key, $value),
                'L2' => $this->config['l2_enable'] && $this->l2Cache->set($key, $value, $ttl ?? $this->config['l2_ttl']),
                'L3' => $this->config['l3_enable'] && $this->l3Cache->set($key, $value, $ttl ?? $this->config['l3_ttl']),
                default => false,
            };
            
            $success = $success && $levelSuccess;
        }

        return $success;
    }

    /**
     * Remember pattern with multi-level caching
     * 
     * @param string $key Cache key
     * @param callable $callback Callback to execute on cache miss
     * @param int|null $ttl TTL in seconds
     * @return mixed Cached or computed value
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
     * Delete value from all cache levels
     * 
     * @param string $key Cache key
     * @return bool Success status
     */
    public function delete(string $key): bool
    {
        $this->stats['invalidations']++;
        $success = true;

        if ($this->config['l1_enable']) {
            $success = $this->l1Cache->delete($key) && $success;
        }
        if ($this->config['l2_enable']) {
            $success = $this->l2Cache->delete($key) && $success;
        }
        if ($this->config['l3_enable']) {
            $success = $this->l3Cache->delete($key) && $success;
        }

        return $success;
    }

    /**
     * Clear all cache levels
     * 
     * @return bool Success status
     */
    public function clear(): bool
    {
        $success = true;

        if ($this->config['l1_enable']) {
            $success = $this->l1Cache->clear() && $success;
        }
        if ($this->config['l2_enable']) {
            $success = $this->l2Cache->clear() && $success;
        }
        if ($this->config['l3_enable']) {
            $success = $this->l3Cache->clear() && $success;
        }
        if ($this->config['l4_enable']) {
            $success = $this->l4Cache->clear() && $success;
        }

        return $success;
    }

    /**
     * Get query result cache (L4)
     * 
     * @return QueryResultCache
     */
    public function queries(): QueryResultCache
    {
        return $this->l4Cache;
    }

    /**
     * Invalidate entity-related caches
     * 
     * @param string $entityType Entity type code
     * @param int|null $entityId Entity ID (null = all entities of type)
     * @return int Number of cache entries invalidated
     */
    public function invalidateEntity(string $entityType, ?int $entityId = null): int
    {
        $invalidated = 0;

        // Invalidate specific entity
        if ($entityId !== null) {
            $key = "entity:{$entityType}:{$entityId}";
            if ($this->delete($key)) {
                $invalidated++;
            }
            
            // Invalidate L4 query cache
            if ($this->config['l4_enable']) {
                $invalidated += $this->l4Cache->invalidateByEntity($entityType, $entityId);
            }
        } else {
            // Invalidate all entities of type
            if ($this->config['l4_enable']) {
                $invalidated += $this->l4Cache->invalidateByEntityType($entityType);
            }
        }

        $this->stats['invalidations'] += $invalidated;
        return $invalidated;
    }

    /**
     * Invalidate by tag
     * 
     * @param string $tag Tag to invalidate
     * @return int Number of cache entries invalidated
     */
    public function invalidateByTag(string $tag): int
    {
        $invalidated = 0;

        if ($this->config['l4_enable']) {
            $invalidated = $this->l4Cache->invalidateByTag($tag);
        }

        $this->stats['invalidations'] += $invalidated;
        return $invalidated;
    }

    /**
     * Warm up cache with pre-computed data
     * 
     * @param array $data Array of key => value pairs
     * @param int|null $ttl TTL in seconds
     * @return int Number of entries cached
     */
    public function warmUp(array $data, ?int $ttl = null): int
    {
        $cached = 0;

        foreach ($data as $key => $value) {
            if ($this->set($key, $value, $ttl)) {
                $cached++;
            }
        }

        return $cached;
    }

    /**
     * Get comprehensive cache statistics
     * 
     * @return array Statistics from all cache levels
     */
    public function getStats(): array
    {
        $l1Stats = $this->config['l1_enable'] ? $this->l1Cache->getStats() : [];
        $l2Stats = $this->config['l2_enable'] ? $this->l2Cache->getStats() : [];
        $l3Stats = $this->config['l3_enable'] ? $this->l3Cache->getStats() : [];
        $l4Stats = $this->config['l4_enable'] ? $this->l4Cache->getStats() : [];

        $totalRequests = $this->stats['total_requests'];
        $totalHits = $this->stats['l1_hits'] + $this->stats['l2_hits'] + 
                     $this->stats['l3_hits'] + $this->stats['l4_hits'];
        $overallHitRate = $totalRequests > 0 ? ($totalHits / $totalRequests) * 100 : 0;

        return [
            'overall' => [
                'total_requests' => $totalRequests,
                'total_hits' => $totalHits,
                'hit_rate' => round($overallHitRate, 2),
                'cache_sets' => $this->stats['cache_sets'],
                'invalidations' => $this->stats['invalidations'],
            ],
            'l1' => array_merge($l1Stats, [
                'hits' => $this->stats['l1_hits'],
                'enabled' => $this->config['l1_enable'],
            ]),
            'l2' => array_merge($l2Stats, [
                'hits' => $this->stats['l2_hits'],
                'enabled' => $this->config['l2_enable'],
            ]),
            'l3' => array_merge($l3Stats, [
                'hits' => $this->stats['l3_hits'],
                'enabled' => $this->config['l3_enable'],
            ]),
            'l4' => array_merge($l4Stats, [
                'hits' => $this->stats['l4_hits'],
                'enabled' => $this->config['l4_enable'],
            ]),
        ];
    }

    /**
     * Reset all statistics
     */
    public function resetStats(): void
    {
        $this->stats = [
            'l1_hits' => 0,
            'l2_hits' => 0,
            'l3_hits' => 0,
            'l4_hits' => 0,
            'total_requests' => 0,
            'cache_sets' => 0,
            'invalidations' => 0,
        ];

        if ($this->config['l1_enable']) $this->l1Cache->resetStats();
        if ($this->config['l2_enable']) $this->l2Cache->resetStats();
        if ($this->config['l3_enable']) $this->l3Cache->resetStats();
        if ($this->config['l4_enable']) $this->l4Cache->resetStats();
    }

    /**
     * Get individual cache layer
     * 
     * @param string $level Cache level (L1/L2/L3/L4)
     * @return RequestCache|MemoryCache|PersistentCache|QueryResultCache|null
     */
    public function getLayer(string $level): mixed
    {
        return match(strtoupper($level)) {
            'L1' => $this->l1Cache,
            'L2' => $this->l2Cache,
            'L3' => $this->l3Cache,
            'L4' => $this->l4Cache,
            default => null,
        };
    }

    /**
     * Check cache health
     * 
     * @return array Health status of all cache layers
     */
    public function healthCheck(): array
    {
        return [
            'l1' => [
                'enabled' => $this->config['l1_enable'],
                'available' => $this->l1Cache->isAvailable(),
                'status' => 'ok',
            ],
            'l2' => [
                'enabled' => $this->config['l2_enable'],
                'available' => $this->l2Cache->isAvailable(),
                'driver' => get_class($this->l2Cache->getDriver()),
                'status' => $this->l2Cache->isAvailable() ? 'ok' : 'degraded',
            ],
            'l3' => [
                'enabled' => $this->config['l3_enable'],
                'available' => $this->l3Cache->isAvailable(),
                'driver' => get_class($this->l3Cache->getDriver()),
                'status' => $this->l3Cache->isAvailable() ? 'ok' : 'degraded',
            ],
            'l4' => [
                'enabled' => $this->config['l4_enable'],
                'available' => $this->l4Cache->isEnabled(),
                'status' => $this->l4Cache->isEnabled() ? 'ok' : 'disabled',
            ],
        ];
    }

    /**
     * Initialize all cache layers
     */
    private function initializeCaches(): void
    {
        $this->l1Cache = new RequestCache();
        $this->l2Cache = new MemoryCache(null, $this->config['l2_ttl']);
        $this->l3Cache = new PersistentCache(null, $this->config['l3_ttl']);
        
        // L4 uses L2 driver for storage
        $this->l4Cache = new QueryResultCache(
            $this->l2Cache->getDriver(),
            $this->config['l4_ttl'],
            $this->config['l4_enable']
        );
    }

    /**
     * Get value from specific cache level
     */
    private function getFromLevel(string $key, string $level): mixed
    {
        return match(strtoupper($level)) {
            'L1' => $this->config['l1_enable'] ? $this->l1Cache->get($key) : null,
            'L2' => $this->config['l2_enable'] ? $this->l2Cache->get($key) : null,
            'L3' => $this->config['l3_enable'] ? $this->l3Cache->get($key) : null,
            default => null,
        };
    }
}
