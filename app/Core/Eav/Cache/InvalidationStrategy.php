<?php

namespace App\Core\Eav\Cache;

/**
 * Cache Invalidation Strategy
 * 
 * Defines strategies for event-driven cache invalidation.
 * - Entity-based invalidation
 * - Tag-based invalidation
 * - Time-based invalidation
 * - Cascade invalidation
 * 
 * @package App\Core\Eav\Cache
 */
class InvalidationStrategy
{
    private CacheManager $cacheManager;
    private array $config;
    private array $invalidationLog = [];

    /**
     * @param CacheManager $cacheManager Cache manager instance
     * @param array $config Invalidation configuration
     */
    public function __construct(CacheManager $cacheManager, array $config = [])
    {
        $this->cacheManager = $cacheManager;
        $this->config = array_merge([
            'enable_cascade' => true,
            'enable_logging' => false,
            'auto_invalidate' => true,
            'invalidation_delay' => 0, // Delay in seconds (0 = immediate)
        ], $config);
    }

    /**
     * Invalidate on entity save
     * 
     * @param string $entityType Entity type code
     * @param int $entityId Entity ID
     * @param array $changedAttributes Changed attribute codes
     * @return int Number of cache entries invalidated
     */
    public function onEntitySave(string $entityType, int $entityId, array $changedAttributes = []): int
    {
        if (!$this->config['auto_invalidate']) {
            return 0;
        }

        $invalidated = 0;
        
        // Invalidate entity cache
        $invalidated += $this->cacheManager->invalidateEntity($entityType, $entityId);
        
        // Invalidate query caches tagged with this entity
        $tags = [
            "entity:{$entityType}:{$entityId}",
            "entity_type:{$entityType}",
        ];
        
        // Add attribute-specific tags
        foreach ($changedAttributes as $attributeCode) {
            $tags[] = "attribute:{$entityType}:{$attributeCode}";
        }
        
        foreach ($tags as $tag) {
            $invalidated += $this->cacheManager->invalidateByTag($tag);
        }
        
        // Cascade invalidation
        if ($this->config['enable_cascade']) {
            $invalidated += $this->cascadeInvalidation($entityType, $entityId);
        }
        
        $this->log('entity_save', $entityType, $entityId, $invalidated);
        
        return $invalidated;
    }

    /**
     * Invalidate on entity delete
     * 
     * @param string $entityType Entity type code
     * @param int $entityId Entity ID
     * @return int Number of cache entries invalidated
     */
    public function onEntityDelete(string $entityType, int $entityId): int
    {
        if (!$this->config['auto_invalidate']) {
            return 0;
        }

        $invalidated = 0;
        
        // Invalidate entity cache
        $invalidated += $this->cacheManager->invalidateEntity($entityType, $entityId);
        
        // Invalidate all related query caches
        $tags = [
            "entity:{$entityType}:{$entityId}",
            "entity_type:{$entityType}",
        ];
        
        foreach ($tags as $tag) {
            $invalidated += $this->cacheManager->invalidateByTag($tag);
        }
        
        $this->log('entity_delete', $entityType, $entityId, $invalidated);
        
        return $invalidated;
    }

    /**
     * Invalidate on bulk operation
     * 
     * @param string $entityType Entity type code
     * @param array $entityIds Array of entity IDs affected
     * @return int Number of cache entries invalidated
     */
    public function onBulkOperation(string $entityType, array $entityIds): int
    {
        if (!$this->config['auto_invalidate']) {
            return 0;
        }

        $invalidated = 0;
        
        // Invalidate individual entities
        foreach ($entityIds as $entityId) {
            $invalidated += $this->cacheManager->invalidateEntity($entityType, $entityId);
        }
        
        // Invalidate entity type collections
        $invalidated += $this->cacheManager->invalidateByTag("entity_type:{$entityType}");
        
        $this->log('bulk_operation', $entityType, count($entityIds), $invalidated);
        
        return $invalidated;
    }

    /**
     * Invalidate on attribute value change
     * 
     * @param string $entityType Entity type code
     * @param int $entityId Entity ID
     * @param string $attributeCode Attribute code
     * @param mixed $oldValue Old value
     * @param mixed $newValue New value
     * @return int Number of cache entries invalidated
     */
    public function onAttributeChange(
        string $entityType,
        int $entityId,
        string $attributeCode,
        mixed $oldValue,
        mixed $newValue
    ): int {
        if (!$this->config['auto_invalidate']) {
            return 0;
        }

        $invalidated = 0;
        
        // Invalidate entity cache
        $invalidated += $this->cacheManager->invalidateEntity($entityType, $entityId);
        
        // Invalidate attribute-specific caches
        $tags = [
            "attribute:{$entityType}:{$attributeCode}",
            "entity:{$entityType}:{$entityId}",
        ];
        
        foreach ($tags as $tag) {
            $invalidated += $this->cacheManager->invalidateByTag($tag);
        }
        
        $this->log('attribute_change', $entityType, $entityId, $invalidated, [
            'attribute' => $attributeCode,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
        
        return $invalidated;
    }

    /**
     * Invalidate collection queries
     * 
     * @param string $entityType Entity type code
     * @param array $filters Filters that changed
     * @return int Number of cache entries invalidated
     */
    public function onCollectionChange(string $entityType, array $filters = []): int
    {
        if (!$this->config['auto_invalidate']) {
            return 0;
        }

        $invalidated = $this->cacheManager->invalidateByTag("entity_type:{$entityType}");
        
        $this->log('collection_change', $entityType, null, $invalidated, ['filters' => $filters]);
        
        return $invalidated;
    }

    /**
     * Invalidate by custom tag
     * 
     * @param string $tag Tag to invalidate
     * @return int Number of cache entries invalidated
     */
    public function invalidateByTag(string $tag): int
    {
        $invalidated = $this->cacheManager->invalidateByTag($tag);
        
        $this->log('tag_invalidation', $tag, null, $invalidated);
        
        return $invalidated;
    }

    /**
     * Invalidate by multiple tags
     * 
     * @param array $tags Tags to invalidate
     * @return int Number of cache entries invalidated
     */
    public function invalidateByTags(array $tags): int
    {
        $invalidated = 0;
        
        foreach ($tags as $tag) {
            $invalidated += $this->invalidateByTag($tag);
        }
        
        return $invalidated;
    }

    /**
     * Schedule delayed invalidation
     * 
     * @param callable $callback Invalidation callback
     * @param int $delay Delay in seconds
     */
    public function scheduleInvalidation(callable $callback, int $delay): void
    {
        if ($delay <= 0) {
            $callback();
            return;
        }
        
        // In production, use queue/scheduler
        // For now, execute immediately
        $callback();
    }

    /**
     * Cascade invalidation to related entities
     * 
     * @param string $entityType Entity type code
     * @param int $entityId Entity ID
     * @return int Number of cache entries invalidated
     */
    private function cascadeInvalidation(string $entityType, int $entityId): int
    {
        $invalidated = 0;
        
        // Invalidate parent/child relationships
        $tags = [
            "parent:{$entityType}:{$entityId}",
            "child:{$entityType}:{$entityId}",
        ];
        
        foreach ($tags as $tag) {
            $invalidated += $this->cacheManager->invalidateByTag($tag);
        }
        
        return $invalidated;
    }

    /**
     * Log invalidation event
     */
    private function log(string $event, string $entityType, ?int $entityId, int $invalidated, array $extra = []): void
    {
        if (!$this->config['enable_logging']) {
            return;
        }

        $this->invalidationLog[] = [
            'timestamp' => microtime(true),
            'event' => $event,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'invalidated' => $invalidated,
            'extra' => $extra,
        ];
    }

    /**
     * Get invalidation log
     * 
     * @return array Invalidation events
     */
    public function getLog(): array
    {
        return $this->invalidationLog;
    }

    /**
     * Clear invalidation log
     */
    public function clearLog(): void
    {
        $this->invalidationLog = [];
    }

    /**
     * Get invalidation statistics
     * 
     * @return array Statistics about invalidations
     */
    public function getStats(): array
    {
        if (empty($this->invalidationLog)) {
            return [
                'total_events' => 0,
                'total_invalidated' => 0,
                'by_event' => [],
                'by_entity_type' => [],
            ];
        }

        $stats = [
            'total_events' => count($this->invalidationLog),
            'total_invalidated' => 0,
            'by_event' => [],
            'by_entity_type' => [],
        ];

        foreach ($this->invalidationLog as $entry) {
            $stats['total_invalidated'] += $entry['invalidated'];
            
            // Count by event type
            if (!isset($stats['by_event'][$entry['event']])) {
                $stats['by_event'][$entry['event']] = 0;
            }
            $stats['by_event'][$entry['event']]++;
            
            // Count by entity type
            if ($entry['entity_type']) {
                if (!isset($stats['by_entity_type'][$entry['entity_type']])) {
                    $stats['by_entity_type'][$entry['entity_type']] = 0;
                }
                $stats['by_entity_type'][$entry['entity_type']]++;
            }
        }

        return $stats;
    }

    /**
     * Enable auto-invalidation
     */
    public function enable(): void
    {
        $this->config['auto_invalidate'] = true;
    }

    /**
     * Disable auto-invalidation
     */
    public function disable(): void
    {
        $this->config['auto_invalidate'] = false;
    }

    /**
     * Check if auto-invalidation is enabled
     */
    public function isEnabled(): bool
    {
        return $this->config['auto_invalidate'];
    }
}
