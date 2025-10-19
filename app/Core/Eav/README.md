# EAV Library - Phase 4: Performance Enhancement

## Overview

This is the **Entity-Attribute-Value (EAV) Library Phase 4** implementation focusing on performance optimization through:

- **Multi-Level Caching** (L1-L4 cache layers)
- **Flat Table Storage** for high-read entities  
- **Query Result Caching** with intelligent invalidation
- **Batch Operations** for bulk data processing
- **Performance Monitoring** and profiling tools

## Architecture

### Performance Layer Components

```
Application Layer
├── Entity Manager
└── Query Builder
    ↓
Performance Layer
├── Cache Manager (L1-L4)
├── Flat Table Engine
├── Batch Processor
└── Performance Monitor
    ↓
Storage Layer
├── EAV Tables (Normalized)
└── Flat Tables (Denormalized)
```

### Multi-Level Cache Strategy

#### L1: Request-Scoped Cache
- **Scope**: Single HTTP request
- **Lifetime**: Request duration only
- **Implementation**: PHP instance properties
- **Storage**: Entity instances, EntityType objects
- **Memory Impact**: Minimal (< 1MB typical)
- **Classes**: `RequestCache`, `IdentityMap`

#### L2: Application Memory Cache
- **Scope**: PHP process/worker
- **Lifetime**: 5-15 minutes (configurable)
- **Implementation**: APCu or static arrays
- **Storage**: Attribute metadata, entity type definitions
- **Memory Impact**: Low (5-20MB typical)
- **Classes**: `MemoryCache`, `ApcuDriver`, `StaticDriver`

#### L3: Persistent Cache
- **Scope**: Application-wide
- **Lifetime**: 1-24 hours (configurable)
- **Implementation**: File cache or Redis
- **Storage**: Serialized entities, denormalized data
- **Memory Impact**: External (configurable)
- **Classes**: `PersistentCache`, `FileDriver`, `RedisDriver`

#### L4: Query Result Cache
- **Scope**: Application-wide
- **Lifetime**: 30s-5min (configurable)
- **Implementation**: Redis with query signature hashing
- **Storage**: Query result sets, aggregated data
- **Memory Impact**: Medium (50-200MB typical)
- **Classes**: `QueryResultCache`, `QuerySignature`

## Installation & Setup

### 1. Directory Structure

```
app/Core/Eav/
├── Cache/
│   ├── Driver/
│   │   ├── ApcuDriver.php
│   │   ├── StaticDriver.php
│   │   ├── FileDriver.php
│   │   └── RedisDriver.php
│   ├── CacheManager.php
│   ├── RequestCache.php
│   ├── IdentityMap.php
│   ├── MemoryCache.php
│   ├── PersistentCache.php
│   ├── QueryResultCache.php
│   ├── QuerySignature.php
│   ├── InvalidationStrategy.php
│   └── TagManager.php
├── Entity/
│   ├── Entity.php
│   ├── EntityType.php
│   ├── Attribute.php
│   └── EntityManager.php
├── Storage/
│   ├── StorageStrategy.php
│   ├── EavStorageStrategy.php
│   └── FlatTableStorageStrategy.php
├── Query/
│   └── QueryBuilder.php
├── FlatTable/
│   ├── FlatTableEngine.php
│   ├── EligibilityAnalyzer.php
│   ├── SchemaGenerator.php
│   ├── SyncManager.php
│   └── QueryRouter.php
├── Batch/
│   ├── BatchProcessor.php
│   ├── BatchInsertStrategy.php
│   ├── BatchUpdateStrategy.php
│   ├── BatchDeleteStrategy.php
│   └── BatchLoadStrategy.php
├── Performance/
│   ├── PerformanceMonitor.php
│   ├── QueryProfiler.php
│   ├── CacheProfiler.php
│   └── MetricsCollector.php
├── Event/
│   ├── CacheInvalidationListener.php
│   └── FlatTableSyncListener.php
├── Module.php
├── config.php
└── README.md
```

### 2. Configuration

Edit `app/Core/Eav/config.php`:

```php
return [
    'enable_performance_layer' => true,
    
    'cache' => [
        'enable' => true,
        'default_ttl' => 3600,
        'l1_enable' => true,
        'l2_enable' => true,
        'l2_driver' => 'apcu', // or 'static'
        'l3_enable' => true,
        'l3_driver' => 'file', // or 'redis'
        'l4_enable' => true,
    ],
    
    'flat_tables' => [
        'enable' => true,
        'min_attributes' => 10,
        'sync_mode' => 'immediate', // or 'deferred', 'rebuild'
    ],
    
    'batch' => [
        'max_size' => 1000,
        'chunk_size' => 100,
    ],
    
    'monitoring' => [
        'enable' => true,
        'sample_rate' => 1.0,
    ],
];
```

### 3. Database Migrations

Run migrations to create EAV tables:

```bash
php migrations/migrate.php
```

Tables created:
- `eav_entity` - Main entity records
- `eav_attribute` - Attribute definitions
- `eav_entity_varchar` - String values
- `eav_entity_int` - Integer values
- `eav_entity_decimal` - Decimal values
- `eav_entity_datetime` - Date/time values
- `eav_entity_text` - Text values
- `eav_flat_metadata` - Flat table tracking

## Usage Examples

### Basic Entity Operations

```php
use Core\Eav\Entity\EntityManager;

// Get entity manager from DI container
$entityManager = $di->get(EntityManager::class);

// Create new entity
$product = $entityManager->create('product');
$product->setAttribute('name', 'Laptop');
$product->setAttribute('sku', 'LAP-001');
$product->setAttribute('price', 999.99);
$product->setAttribute('qty', 50);

// Save (L1 cache populated automatically)
$entityManager->save($product);

// Load entity (checks L1 → L2 → L3 → Database)
$loadedProduct = $entityManager->load('product', $product->getId());

// Update with dirty tracking
$loadedProduct->setAttribute('price', 899.99);
$entityManager->save($loadedProduct); // Only price updated

// Delete
$entityManager->delete($loadedProduct);
```

### Batch Operations

```php
use Core\Eav\Batch\BatchProcessor;

$batchProcessor = $di->get(BatchProcessor::class);

// Batch insert (10-100x faster than individual inserts)
$products = [];
for ($i = 0; $i < 100; $i++) {
    $product = $entityManager->create('product');
    $product->setAttribute('name', "Product $i");
    $product->setAttribute('sku', "SKU-$i");
    $product->setAttribute('price', rand(10, 1000));
    $products[] = $product;
}

$batchProcessor->batchInsert($products);

// Batch update
foreach ($products as $product) {
    $product->setAttribute('price', $product->getAttribute('price') * 0.9);
}
$batchProcessor->batchUpdate($products);

// Batch load
$ids = [1, 2, 3, 4, 5];
$entities = $batchProcessor->batchLoad('product', $ids);

// Batch delete
$batchProcessor->batchDelete('product', $ids);
```

### Query with Caching

```php
use Core\Eav\Query\QueryBuilder;

$queryBuilder = $di->get(QueryBuilder::class);

// Query result cached in L4
$products = $queryBuilder
    ->entityType('product')
    ->addAttributeFilter('status', 1)
    ->addAttributeFilter('price', '>=', 100)
    ->addAttributeSort('name', 'ASC')
    ->limit(20)
    ->getResult();

// Second call returns cached result (no DB query)
$cachedProducts = $queryBuilder
    ->entityType('product')
    ->addAttributeFilter('status', 1)
    ->addAttributeFilter('price', '>=', 100)
    ->addAttributeSort('name', 'ASC')
    ->limit(20)
    ->getResult();
```

### Flat Table Management

```php
use Core\Eav\FlatTable\FlatTableEngine;

$flatTableEngine = $di->get(FlatTableEngine::class);

// Analyze entity type for flat table eligibility
$analysis = $flatTableEngine->analyzeEntityType('product');
if ($analysis['eligible']) {
    // Create flat table
    $flatTableEngine->createFlatTable('product');
    
    // Queries automatically routed to flat table for performance
    $products = $queryBuilder
        ->entityType('product')
        ->addAttributeFilter('status', 1)
        ->getResult(); // Uses flat table if available
}

// Manual rebuild (if needed)
$flatTableEngine->rebuildFlatTable('product');

// Check flat table status
$status = $flatTableEngine->getFlatTableStatus('product');
```

### Performance Monitoring

```php
use Core\Eav\Performance\PerformanceMonitor;

$perfMonitor = $di->get(PerformanceMonitor::class);

// Get cache statistics
$cacheStats = $perfMonitor->getCacheStats();
/*
[
    'l1' => ['hits' => 450, 'misses' => 50, 'hit_rate' => 90.0],
    'l2' => ['hits' => 380, 'misses' => 120, 'hit_rate' => 76.0],
    'l3' => ['hits' => 250, 'misses' => 250, 'hit_rate' => 50.0],
    'l4' => ['hits' => 180, 'misses' => 320, 'hit_rate' => 36.0],
]
*/

// Get query performance metrics
$queryStats = $perfMonitor->getQueryStats();
/*
[
    'total_queries' => 125,
    'avg_time' => 45.2, // ms
    'p95_time' => 180.5, // ms
    'slow_queries' => 3,
]
*/

// Get flat table metrics
$flatTableStats = $perfMonitor->getFlatTableStats();
/*
[
    'product' => [
        'read_routing_pct' => 95.5,
        'avg_sync_latency' => 12.3, // ms
        'last_rebuild' => '2025-10-17 14:30:00',
    ],
]
*/
```

## Cache Invalidation

Cache invalidation happens automatically through event listeners:

| Event | L1 | L2 | L3 | L4 |
|-------|----|----|----|----|
| Entity created | - | - | - | Invalidate entity type queries |
| Entity updated | Clear instance | - | Invalidate entity ID | Invalidate entity type queries |
| Entity deleted | Clear instance | - | Invalidate entity ID | Invalidate entity type queries |
| Attribute added | Clear all | Clear metadata | Invalidate entity type | Invalidate all queries |
| Schema sync | Clear all | Clear all | Clear all | Clear all |

Manual invalidation:

```php
use Core\Eav\Cache\CacheManager;

$cacheManager = $di->get(CacheManager::class);

// Clear specific entity
$cacheManager->invalidateEntity('product', $productId);

// Clear all entities of a type
$cacheManager->invalidateEntityType('product');

// Clear all caches
$cacheManager->clearAll();
```

## Performance Benchmarks

Based on design specifications:

| Operation | Without Cache | With Full Cache | Improvement |
|-----------|---------------|-----------------|-------------|
| Single entity load | 15ms | 0.5ms | 30× faster |
| Batch load (100 entities) | 1500ms | 150ms | 10× faster |
| Complex query | 200ms | 5ms | 40× faster |
| Batch insert (100 entities) | 3000ms | 300ms | 10× faster |

| Cache Layer | Target Hit Rate | Typical Hit Rate |
|-------------|-----------------|------------------|
| L1 (Request) | > 80% | 85-95% |
| L2 (Memory) | > 70% | 75-85% |
| L3 (Persistent) | > 60% | 65-75% |
| L4 (Query) | > 50% | 55-65% |

## Configuration Tuning

### High-Read, Low-Write Workload

```php
'cache' => [
    'l3_ttl' => 7200, // 2 hours
    'l4_ttl' => 600,  // 10 minutes
],
'flat_tables' => [
    'enable' => true,
    'sync_mode' => 'deferred',
],
```

### High-Write Workload

```php
'cache' => [
    'l3_ttl' => 300,  // 5 minutes
    'l4_ttl' => 60,   // 1 minute
],
'flat_tables' => [
    'enable' => false, // Or use 'rebuild' mode
],
```

### Development Environment

```php
'cache' => [
    'l2_enable' => false,
    'l3_enable' => false,
    'l4_enable' => false,
],
'monitoring' => [
    'enable' => true,
    'sample_rate' => 1.0,
],
```

## API Reference

### Entity Manager

```php
$entityManager->create(string $entityType): Entity
$entityManager->load(string $entityType, int $id): ?Entity
$entityManager->save(Entity $entity): void
$entityManager->delete(Entity $entity): void
```

### Batch Processor

```php
$batchProcessor->batchInsert(array $entities): void
$batchProcessor->batchUpdate(array $entities): void
$batchProcessor->batchDelete(string $entityType, array $ids): void
$batchProcessor->batchLoad(string $entityType, array $ids): array
```

### Query Builder

```php
$queryBuilder->entityType(string $type): self
$queryBuilder->addAttributeFilter(string $code, mixed $value): self
$queryBuilder->addAttributeSort(string $code, string $direction): self
$queryBuilder->limit(int $limit): self
$queryBuilder->offset(int $offset): self
$queryBuilder->getResult(): array
```

### Cache Manager

```php
$cacheManager->get(string $key): mixed
$cacheManager->set(string $key, mixed $value, ?int $ttl = null): void
$cacheManager->invalidateEntity(string $entityType, int $id): void
$cacheManager->invalidateEntityType(string $entityType): void
$cacheManager->clearAll(): void
```

## Testing

Run the example scripts:

```bash
php examples/eav_cache_demo.php
php examples/eav_flat_table_demo.php
php examples/eav_batch_operations_demo.php
php examples/eav_performance_monitoring_demo.php
```

## Troubleshooting

### Cache Not Working

1. Check if APCu extension is installed: `php -m | grep apcu`
2. Verify cache configuration: `'l2_enable' => true`
3. Check cache directory permissions (for file driver)

### Poor Cache Hit Rate

1. Increase TTL values
2. Check cache invalidation frequency
3. Review query patterns for cache-friendliness

### Flat Table Not Updating

1. Check sync mode: `'sync_mode' => 'immediate'`
2. Verify flat table exists: `$flatTableEngine->getFlatTableStatus()`
3. Manual rebuild: `$flatTableEngine->rebuildFlatTable()`

### Slow Queries

1. Enable query profiling
2. Check if flat tables are being used
3. Review query complexity and add indexes

## License

Part of the Core Framework. See main application license.

## Support

For issues and questions, refer to the main project documentation.
