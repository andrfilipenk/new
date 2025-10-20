<?php
// app/Eav/Cache/QueryCache.php
namespace Eav\Cache;

/**
 * Query Cache
 * 
 * Cache query results with smart invalidation on entity changes
 */
class QueryCache
{
    private $db;
    private CacheManager $cacheManager;
    private int $defaultTtl = 600; // 10 minutes

    public function __construct($db, CacheManager $cacheManager)
    {
        $this->db = $db;
        $this->cacheManager = $cacheManager;
    }

    /**
     * Get or execute a query with caching
     */
    public function rememberQuery(string $sql, array $bindings, ?int $ttl = null): array
    {
        $queryHash = $this->hashQuery($sql, $bindings);
        $cacheKey = "query:{$queryHash}";

        return $this->cacheManager->remember($cacheKey, function() use ($sql, $bindings) {
            return $this->executeQuery($sql, $bindings);
        }, $ttl ?? $this->defaultTtl);
    }

    /**
     * Get cached query result
     */
    public function get(string $sql, array $bindings): ?array
    {
        $queryHash = $this->hashQuery($sql, $bindings);
        $cacheKey = "query:{$queryHash}";

        return $this->cacheManager->get($cacheKey);
    }

    /**
     * Cache a query result
     */
    public function set(string $sql, array $bindings, array $result, ?int $ttl = null): bool
    {
        $queryHash = $this->hashQuery($sql, $bindings);
        $cacheKey = "query:{$queryHash}";

        return $this->cacheManager->set($cacheKey, $result, $ttl ?? $this->defaultTtl);
    }

    /**
     * Invalidate a specific query
     */
    public function invalidate(string $sql, array $bindings): void
    {
        $queryHash = $this->hashQuery($sql, $bindings);
        $this->cacheManager->invalidateQuery($queryHash);
    }

    /**
     * Invalidate all queries for an entity type
     */
    public function invalidateEntityType(int $entityTypeId): void
    {
        $this->cacheManager->invalidateEntityTypeQueries($entityTypeId);
    }

    /**
     * Clear all query cache
     */
    public function clear(): bool
    {
        return $this->cacheManager->clear('query:*');
    }

    /**
     * Generate hash for query caching
     */
    private function hashQuery(string $sql, array $bindings): string
    {
        return md5($sql . serialize($bindings));
    }

    /**
     * Execute query (placeholder for actual execution)
     */
    private function executeQuery(string $sql, array $bindings): array
    {
        // This would use the Database class to execute the query
        // For now, returning empty array as placeholder
        return [];
    }

    /**
     * Tag a query with entity type for invalidation
     */
    public function tagQuery(string $sql, array $bindings, int $entityTypeId): void
    {
        $queryHash = $this->hashQuery($sql, $bindings);
        $tagKey = "query:tag:{$queryHash}";
        
        $this->cacheManager->set($tagKey, [
            'entity_type_id' => $entityTypeId,
            'timestamp' => time()
        ], 86400); // 24 hours
    }
}
