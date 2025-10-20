<?php
// app/Core/Eav/Cache/RequestCache.php
namespace Core\Eav\Cache;

/**
 * L1: Request-Scoped Cache
 * 
 * Operates within a single HTTP request lifecycle.
 * Stores live object instances in memory without serialization.
 * Automatically cleared at request end.
 */
class RequestCache
{
    private array $cache = [];
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
    ];

    /**
     * Get value from cache
     */
    public function get(string $key): mixed
    {
        if (isset($this->cache[$key])) {
            $this->stats['hits']++;
            return $this->cache[$key];
        }
        
        $this->stats['misses']++;
        return null;
    }

    /**
     * Set value in cache
     */
    public function set(string $key, mixed $value): void
    {
        $this->cache[$key] = $value;
        $this->stats['sets']++;
    }

    /**
     * Check if key exists
     */
    public function has(string $key): bool
    {
        return isset($this->cache[$key]);
    }

    /**
     * Delete specific key
     */
    public function delete(string $key): void
    {
        unset($this->cache[$key]);
    }

    /**
     * Clear all cache
     */
    public function clear(): void
    {
        $this->cache = [];
    }

    /**
     * Clear by prefix
     */
    public function clearByPrefix(string $prefix): void
    {
        foreach (array_keys($this->cache) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->cache[$key]);
            }
        }
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
            'size' => count($this->cache),
        ];
    }

    /**
     * Get memory usage estimate (bytes)
     */
    public function getMemoryUsage(): int
    {
        return strlen(serialize($this->cache));
    }
}
