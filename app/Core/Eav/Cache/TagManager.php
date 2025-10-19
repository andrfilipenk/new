<?php

namespace App\Core\Eav\Cache;

use App\Core\Eav\Cache\Driver\CacheDriverInterface;

/**
 * Tag Manager
 * 
 * Manages tag-based cache invalidation for complex relationships.
 * - Tag registration and tracking
 * - Bulk tag operations
 * - Tag hierarchies
 * - Efficient tag-to-key mapping
 * 
 * @package App\Core\Eav\Cache
 */
class TagManager
{
    private CacheDriverInterface $driver;
    private string $tagPrefix = 'tag_index_';
    private int $indexTtl = 86400; // 24 hours

    /**
     * @param CacheDriverInterface $driver Cache driver for storing tag indices
     */
    public function __construct(CacheDriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Tag a cache key
     * 
     * @param string $cacheKey Cache key to tag
     * @param array $tags Tags to apply
     * @return bool Success status
     */
    public function tag(string $cacheKey, array $tags): bool
    {
        if (empty($tags)) {
            return true;
        }

        $success = true;

        foreach ($tags as $tag) {
            $tagKey = $this->makeTagKey($tag);
            
            // Get existing keys for this tag
            $keys = $this->driver->get($tagKey);
            if (!is_array($keys)) {
                $keys = [];
            }
            
            // Add cache key to tag index
            if (!in_array($cacheKey, $keys, true)) {
                $keys[] = $cacheKey;
                $success = $this->driver->set($tagKey, $keys, $this->indexTtl) && $success;
            }
        }

        return $success;
    }

    /**
     * Get all cache keys for a tag
     * 
     * @param string $tag Tag to query
     * @return array Array of cache keys
     */
    public function getKeys(string $tag): array
    {
        $tagKey = $this->makeTagKey($tag);
        $keys = $this->driver->get($tagKey);
        
        return is_array($keys) ? $keys : [];
    }

    /**
     * Get cache keys for multiple tags
     * 
     * @param array $tags Tags to query
     * @param bool $intersect True = keys must have ALL tags, False = keys with ANY tag
     * @return array Array of cache keys
     */
    public function getKeysByTags(array $tags, bool $intersect = false): array
    {
        if (empty($tags)) {
            return [];
        }

        $keySets = [];
        
        foreach ($tags as $tag) {
            $keySets[] = $this->getKeys($tag);
        }

        if ($intersect) {
            // Keys that have ALL tags
            return count($keySets) > 1 ? array_intersect(...$keySets) : $keySets[0];
        } else {
            // Keys that have ANY tag
            return array_unique(array_merge(...$keySets));
        }
    }

    /**
     * Remove tag from a cache key
     * 
     * @param string $cacheKey Cache key
     * @param string $tag Tag to remove
     * @return bool Success status
     */
    public function untag(string $cacheKey, string $tag): bool
    {
        $tagKey = $this->makeTagKey($tag);
        $keys = $this->driver->get($tagKey);
        
        if (!is_array($keys)) {
            return true;
        }

        $index = array_search($cacheKey, $keys, true);
        if ($index !== false) {
            unset($keys[$index]);
            $keys = array_values($keys); // Re-index
            
            if (empty($keys)) {
                return $this->driver->delete($tagKey);
            } else {
                return $this->driver->set($tagKey, $keys, $this->indexTtl);
            }
        }

        return true;
    }

    /**
     * Remove all tags from a cache key
     * 
     * @param string $cacheKey Cache key
     * @param array $tags Tags to remove
     * @return bool Success status
     */
    public function untagAll(string $cacheKey, array $tags): bool
    {
        $success = true;

        foreach ($tags as $tag) {
            $success = $this->untag($cacheKey, $tag) && $success;
        }

        return $success;
    }

    /**
     * Delete all cache keys associated with a tag
     * 
     * @param string $tag Tag to invalidate
     * @return int Number of keys deleted
     */
    public function flush(string $tag): int
    {
        $keys = $this->getKeys($tag);
        
        if (empty($keys)) {
            return 0;
        }

        $deleted = 0;
        
        foreach ($keys as $key) {
            if ($this->driver->delete($key)) {
                $deleted++;
            }
        }

        // Delete tag index
        $this->driver->delete($this->makeTagKey($tag));

        return $deleted;
    }

    /**
     * Delete cache keys for multiple tags
     * 
     * @param array $tags Tags to invalidate
     * @param bool $intersect True = delete keys with ALL tags, False = ANY tag
     * @return int Number of keys deleted
     */
    public function flushTags(array $tags, bool $intersect = false): int
    {
        $keys = $this->getKeysByTags($tags, $intersect);
        
        if (empty($keys)) {
            return 0;
        }

        $deleted = 0;
        
        foreach ($keys as $key) {
            if ($this->driver->delete($key)) {
                $deleted++;
            }
        }

        // Delete tag indices
        foreach ($tags as $tag) {
            $this->driver->delete($this->makeTagKey($tag));
        }

        return $deleted;
    }

    /**
     * Check if a tag exists
     * 
     * @param string $tag Tag to check
     * @return bool True if tag has associated keys
     */
    public function exists(string $tag): bool
    {
        return !empty($this->getKeys($tag));
    }

    /**
     * Get all tags (from cache)
     * Note: This is expensive, use sparingly
     * 
     * @return array Array of tag names
     */
    public function getAllTags(): array
    {
        // This would require a separate tag registry
        // Simplified implementation
        return [];
    }

    /**
     * Count keys for a tag
     * 
     * @param string $tag Tag to count
     * @return int Number of keys
     */
    public function count(string $tag): int
    {
        return count($this->getKeys($tag));
    }

    /**
     * Get tag statistics
     * 
     * @param array $tags Tags to analyze (empty = all accessible tags)
     * @return array Statistics
     */
    public function getStats(array $tags = []): array
    {
        if (empty($tags)) {
            return [
                'total_tags' => 0,
                'total_keys' => 0,
            ];
        }

        $stats = [
            'total_tags' => count($tags),
            'total_keys' => 0,
            'by_tag' => [],
        ];

        foreach ($tags as $tag) {
            $keyCount = $this->count($tag);
            $stats['total_keys'] += $keyCount;
            $stats['by_tag'][$tag] = $keyCount;
        }

        return $stats;
    }

    /**
     * Clean expired tag indices
     * 
     * @return int Number of indices cleaned
     */
    public function cleanup(): int
    {
        // This would require scanning all tag keys
        // Implementation depends on cache driver capabilities
        return 0;
    }

    /**
     * Generate entity-related tags
     * 
     * @param string $entityType Entity type code
     * @param int|null $entityId Entity ID (null = type-level tag)
     * @param array $attributeCodes Attribute codes
     * @return array Generated tags
     */
    public static function generateEntityTags(
        string $entityType,
        ?int $entityId = null,
        array $attributeCodes = []
    ): array {
        $tags = ["entity_type:{$entityType}"];

        if ($entityId !== null) {
            $tags[] = "entity:{$entityType}:{$entityId}";
        }

        foreach ($attributeCodes as $attributeCode) {
            $tags[] = "attribute:{$entityType}:{$attributeCode}";
            
            if ($entityId !== null) {
                $tags[] = "entity_attribute:{$entityType}:{$entityId}:{$attributeCode}";
            }
        }

        return $tags;
    }

    /**
     * Generate collection query tags
     * 
     * @param string $entityType Entity type code
     * @param array $filters Filter criteria
     * @return array Generated tags
     */
    public static function generateCollectionTags(string $entityType, array $filters = []): array
    {
        $tags = ["entity_type:{$entityType}", "collection:{$entityType}"];

        // Add filter-specific tags
        foreach ($filters as $attributeCode => $value) {
            $tags[] = "filter:{$entityType}:{$attributeCode}";
        }

        return $tags;
    }

    /**
     * Generate search query tags
     * 
     * @param string $entityType Entity type code
     * @param array $searchableAttributes Attributes being searched
     * @return array Generated tags
     */
    public static function generateSearchTags(string $entityType, array $searchableAttributes = []): array
    {
        $tags = ["entity_type:{$entityType}", "search:{$entityType}"];

        foreach ($searchableAttributes as $attributeCode) {
            $tags[] = "search_attribute:{$entityType}:{$attributeCode}";
        }

        return $tags;
    }

    /**
     * Generate relationship tags
     * 
     * @param string $parentType Parent entity type
     * @param int $parentId Parent entity ID
     * @param string $childType Child entity type
     * @param int|null $childId Child entity ID (null = all children)
     * @return array Generated tags
     */
    public static function generateRelationTags(
        string $parentType,
        int $parentId,
        string $childType,
        ?int $childId = null
    ): array {
        $tags = [
            "parent:{$parentType}:{$parentId}",
            "relation:{$parentType}:{$childType}",
        ];

        if ($childId !== null) {
            $tags[] = "child:{$childType}:{$childId}";
            $tags[] = "relation:{$parentType}:{$parentId}:{$childType}:{$childId}";
        }

        return $tags;
    }

    /**
     * Generate custom tag
     * 
     * @param string $namespace Tag namespace
     * @param string $identifier Tag identifier
     * @return string Generated tag
     */
    public static function generateCustomTag(string $namespace, string $identifier): string
    {
        return "{$namespace}:{$identifier}";
    }

    /**
     * Make tag index key
     */
    private function makeTagKey(string $tag): string
    {
        return $this->tagPrefix . md5($tag);
    }

    /**
     * Set tag index TTL
     * 
     * @param int $ttl TTL in seconds
     */
    public function setIndexTtl(int $ttl): void
    {
        $this->indexTtl = $ttl;
    }

    /**
     * Get tag index TTL
     * 
     * @return int TTL in seconds
     */
    public function getIndexTtl(): int
    {
        return $this->indexTtl;
    }
}
