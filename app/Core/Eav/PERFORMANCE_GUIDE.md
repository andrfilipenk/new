# EAV Library - Performance Tuning Guide

## Table of Contents

1. [Cache Configuration](#cache-configuration)
2. [Flat Table Optimization](#flat-table-optimization)
3. [Batch Operations Best Practices](#batch-operations-best-practices)
4. [Query Optimization](#query-optimization)
5. [Monitoring & Profiling](#monitoring--profiling)
6. [Common Performance Patterns](#common-performance-patterns)
7. [Troubleshooting](#troubleshooting)

## Cache Configuration

### Understanding Cache Layers

The EAV library implements a 4-tier cache hierarchy. Each layer serves a specific purpose and has different performance characteristics.

#### L1 Cache (Request-Scoped)

**Purpose**: Eliminate duplicate object creation within a single request

**Configuration**:
```php
'cache' => [
    'l1_enable' => true, // Always enabled, no overhead
]
```

**Best Practices**:
- Always enabled - provides free performance gains
- Automatically manages entity identity
- No configuration needed

**Metrics to Monitor**:
- Hit rate target: > 80%
- Memory usage: < 1MB per request

#### L2 Cache (Application Memory)

**Purpose**: Persist metadata and configuration across requests within PHP process

**Configuration**:
```php
'cache' => [
    'l2_enable' => true,
    'l2_driver' => 'apcu', // or 'static'
    'l2_ttl' => 900, // 15 minutes
]
```

**Driver Selection**:

| Driver | Best For | Pros | Cons |
|--------|----------|------|------|
| **apcu** | Production with APCu extension | Shared across requests, persistent | Requires PHP extension |
| **static** | Development or when APCu unavailable | No dependencies | Per-process, not shared |

**Tuning**:

```php
// High-stability environments (infrequent schema changes)
'l2_ttl' => 3600, // 1 hour

// Development (frequent changes)
'l2_ttl' => 60, // 1 minute
```

**Metrics to Monitor**:
- Hit rate target: > 70%
- Memory usage: 5-20MB
- Eviction rate: < 5% of sets

#### L3 Cache (Persistent)

**Purpose**: Store serialized entities and denormalized data across all requests

**Configuration**:
```php
'cache' => [
    'l3_enable' => true,
    'l3_driver' => 'file', // or 'redis'
    'l3_ttl' => 3600, // 1 hour
    'l3_path' => APP_PATH . '../public/cache/eav/',
]
```

**Driver Selection**:

| Driver | Best For | Throughput | Latency | Scalability |
|--------|----------|------------|---------|-------------|
| **file** | Single-server deployments | Medium | Low | Low |
| **redis** | Multi-server, high-traffic | High | Very Low | High |

**Tuning by Workload**:

**Read-Heavy Workload**:
```php
'l3_ttl' => 7200, // 2 hours
'serializer' => 'igbinary', // If available, faster than 'php'
```

**Write-Heavy Workload**:
```php
'l3_ttl' => 300, // 5 minutes
'serializer' => 'php', // Standard, reliable
```

**Balanced Workload**:
```php
'l3_ttl' => 1800, // 30 minutes
```

**Metrics to Monitor**:
- Hit rate target: > 60%
- Memory usage: Configurable (recommend 100-500MB)
- Serialization time: < 5ms per entity

#### L4 Cache (Query Results)

**Purpose**: Cache complex query results to avoid expensive EAV joins

**Configuration**:
```php
'cache' => [
    'l4_enable' => true,
    'l4_driver' => 'file', // 'redis' recommended for production
    'l4_ttl' => 300, // 5 minutes
]
```

**TTL Strategy by Query Type**:

```php
// Volatile data (frequently changing)
'l4_ttl' => 60, // 1 minute

// Semi-volatile data (occasional updates)
'l4_ttl' => 300, // 5 minutes (default)

// Stable data (rare updates)
'l4_ttl' => 1800, // 30 minutes
```

**Metrics to Monitor**:
- Hit rate target: > 50%
- Query time reduction: > 80% on cache hit
- Invalidation frequency: < 10 invalidations/minute

### Cache Invalidation Tuning

**Aggressive Invalidation** (High Consistency):
```php
// Invalidate on any entity change
'cache' => [
    'invalidate_on_save' => true,
    'invalidate_entire_type' => true,
]
```

**Conservative Invalidation** (High Performance):
```php
// Only invalidate specific entities
'cache' => [
    'invalidate_on_save' => true,
    'invalidate_entire_type' => false, // Only invalidate changed entity
]
```

## Flat Table Optimization

### Eligibility Analysis

**When to Use Flat Tables**:

✅ **Good Candidates**:
- High read/write ratio (> 5:1)
- Stable attribute schema (< 1 change/month)
- Many attributes (> 10)
- High query frequency (> 100 queries/hour)
- Consistent attribute population (> 80% entities have all attributes)

❌ **Poor Candidates**:
- Write-heavy workloads
- Frequently changing schema
- Sparse attributes (many NULL values)
- Low query volume

**Configuration**:
```php
'flat_tables' => [
    'enable' => true,
    'min_attributes' => 10,
    'read_write_ratio' => 5.0,
    'min_entity_count' => 1000,
    'min_query_frequency' => 100,
    'attribute_consistency_threshold' => 0.8,
]
```

### Synchronization Modes

#### Immediate Synchronization

**Best For**: Low-volume writes, strong consistency requirements

```php
'flat_table_sync_mode' => 'immediate',
```

**Characteristics**:
- Write latency: +10-30ms per entity save
- Consistency: Always synchronized
- Resource usage: Medium (one transaction per write)

#### Deferred Synchronization

**Best For**: Medium-volume writes, eventual consistency acceptable

```php
'flat_table_sync_mode' => 'deferred',
```

**Characteristics**:
- Write latency: +2-5ms (queue only)
- Consistency: Eventual (< 1 minute delay)
- Resource usage: Low (batched background updates)

**Setup**:
```php
// Requires background worker
// Example cron job:
*/1 * * * * php /path/to/process_flat_table_queue.php
```

#### Rebuild Mode

**Best For**: High-volume writes, batch processing workflows

```php
'flat_table_sync_mode' => 'rebuild',
'rebuild_schedule' => 'daily', // or 'hourly'
```

**Characteristics**:
- Write latency: +1ms (flag only)
- Consistency: Eventual (scheduled)
- Resource usage: Very low during writes, high during rebuild

**Setup**:
```php
// Daily rebuild at 2 AM
0 2 * * * php /path/to/rebuild_flat_tables.php
```

### Index Strategy for Flat Tables

**Automatically Indexed Columns**:
- Primary key (entity_id)
- Filterable attributes
- Searchable attributes

**Manual Index Tuning**:

```sql
-- For frequently filtered combinations
CREATE INDEX idx_product_status_price 
ON eav_flat_product (status, price);

-- For text search
CREATE FULLTEXT INDEX idx_product_name_desc 
ON eav_flat_product (name, description);
```

## Batch Operations Best Practices

### Optimal Batch Sizes

**General Guidelines**:

| Operation | Recommended Batch Size | Maximum Batch Size |
|-----------|------------------------|-------------------|
| Insert | 100-500 | 1000 |
| Update | 50-200 | 500 |
| Delete | 100-500 | 1000 |
| Load | 50-100 | 200 |

**Configuration**:
```php
'batch' => [
    'max_size' => 1000,
    'chunk_size' => 100,
    'transaction_mode' => true,
]
```

### Performance Patterns

#### Pattern 1: Large Import Operations

```php
// DON'T: Loop with individual saves
foreach ($rows as $row) {
    $entity = $entityManager->create('product');
    $entity->setAttributes($row);
    $entityManager->save($entity); // 1 transaction each = slow
}

// DO: Use batch insert
$entities = [];
foreach ($rows as $row) {
    $entity = $entityManager->create('product');
    $entity->setAttributes($row);
    $entities[] = $entity;
    
    if (count($entities) >= 100) {
        $batchProcessor->batchInsert($entities);
        $entities = [];
    }
}
if (!empty($entities)) {
    $batchProcessor->batchInsert($entities); // Final batch
}
```

**Expected Performance**:
- Individual saves: 3000ms for 100 entities
- Batch insert: 300ms for 100 entities (10× faster)

#### Pattern 2: Bulk Updates

```php
// DON'T: Load and save individually
foreach ($ids as $id) {
    $entity = $entityManager->load('product', $id);
    $entity->setAttribute('price', $entity->getAttribute('price') * 0.9);
    $entityManager->save($entity);
}

// DO: Batch load and batch update
$entities = $batchProcessor->batchLoad('product', $ids);
foreach ($entities as $entity) {
    $entity->setAttribute('price', $entity->getAttribute('price') * 0.9);
}
$batchProcessor->batchUpdate($entities);
```

**Expected Performance**:
- Individual: 1500ms for 100 entities
- Batch: 200ms for 100 entities (7.5× faster)

## Query Optimization

### Query Planning

**Understand Query Paths**:

1. **EAV Query Path** (Without Flat Table):
   ```
   SELECT e.*, v.value 
   FROM eav_entity e
   LEFT JOIN eav_entity_varchar v ON e.entity_id = v.entity_id
   LEFT JOIN eav_entity_int i ON e.entity_id = i.entity_id
   -- 5+ table joins for multi-attribute queries
   ```

2. **Flat Table Path**:
   ```
   SELECT * FROM eav_flat_product WHERE status = 1 AND price > 100
   -- Single table scan, much faster
   ```

### Filter Optimization

**Best Practices**:

```php
// ✅ Filter on indexed columns first
$query->addAttributeFilter('status', 1)      // Indexed, selective
      ->addAttributeFilter('price', '>=', 100) // Indexed
      ->addAttributeFilter('name', 'LIKE', '%Laptop%'); // Text search last

// ❌ Avoid non-selective filters first
$query->addAttributeFilter('name', 'LIKE', '%a%') // Returns too many rows
      ->addAttributeFilter('status', 1); // Selective filter too late
```

### Limit and Pagination

**Always Use Limits**:

```php
// ✅ Good
$products = $queryBuilder
    ->entityType('product')
    ->limit(20)
    ->offset($page * 20)
    ->getResult();

// ❌ Bad (loads all entities into memory)
$products = $queryBuilder
    ->entityType('product')
    ->getResult();
```

### Attribute Loading Strategy

**Selective Attribute Loading**:

```php
// Future enhancement: Load only needed attributes
$queryBuilder->selectAttributes(['name', 'sku', 'price'])
              ->entityType('product')
              ->getResult();
```

## Monitoring & Profiling

### Key Performance Indicators

#### Cache Metrics

```php
$perfMonitor = $di->get(PerformanceMonitor::class);
$stats = $perfMonitor->getCacheStats();

// Target KPIs:
// - L1 hit rate: > 80%
// - L2 hit rate: > 70%
// - L3 hit rate: > 60%
// - L4 hit rate: > 50%
```

**Interpreting Results**:

| Hit Rate | Status | Action |
|----------|--------|--------|
| > 80% | Excellent | No action needed |
| 60-80% | Good | Consider increasing TTL |
| 40-60% | Fair | Review access patterns, tune TTL |
| < 40% | Poor | Investigate invalidation frequency, cache size |

#### Query Performance

```php
$queryStats = $perfMonitor->getQueryStats();

// Target KPIs:
// - Average query time: < 50ms
// - 95th percentile: < 200ms
// - Slow queries: < 1% of total
```

**Optimization Actions**:

| Avg Time | P95 Time | Action |
|----------|----------|--------|
| < 50ms | < 200ms | Excellent, no action |
| 50-100ms | 200-500ms | Enable flat tables for heavy entities |
| 100-200ms | > 500ms | Add indexes, optimize queries |
| > 200ms | > 1000ms | Critical: Review architecture, add flat tables |

### Profiling Tools

#### Enable Debug Mode

```php
'monitoring' => [
    'enable' => true,
    'sample_rate' => 1.0, // Profile all requests
    'log_slow_queries' => true,
    'slow_query_threshold' => 200, // ms
]
```

#### Analyze Slow Queries

```php
$slowQueries = $perfMonitor->getSlowQueries();
foreach ($slowQueries as $query) {
    echo "Time: {$query['time']}ms\n";
    echo "SQL: {$query['sql']}\n";
    echo "Entity Type: {$query['entity_type']}\n";
}
```

## Common Performance Patterns

### Pattern: Product Catalog

**Scenario**: 10,000 products, 15 attributes each, 1000 queries/hour

**Optimal Configuration**:
```php
'entity_types' => [
    'product' => [
        'cache_ttl' => 7200, // 2 hours (stable data)
        'enable_flat_table' => true,
        'flat_table_sync_mode' => 'deferred',
        'cache_priority' => 'high',
        'query_cache_enable' => true,
    ],
],
'cache' => [
    'l3_ttl' => 3600,
    'l4_ttl' => 600,
],
```

**Expected Results**:
- Query time: 5-10ms (vs 150ms without optimization)
- Cache hit rate: 85%+
- Database load reduction: 90%

### Pattern: Customer Data

**Scenario**: 50,000 customers, 8 attributes each, low query frequency, frequent updates

**Optimal Configuration**:
```php
'entity_types' => [
    'customer' => [
        'cache_ttl' => 300, // 5 minutes (frequently updated)
        'enable_flat_table' => false, // Low query frequency
        'cache_priority' => 'normal',
        'query_cache_enable' => false,
    ],
],
```

### Pattern: Real-Time Inventory

**Scenario**: Highly volatile data, strong consistency required

**Optimal Configuration**:
```php
'entity_types' => [
    'inventory' => [
        'cache_ttl' => 60, // 1 minute only
        'enable_flat_table' => false,
        'cache_priority' => 'low',
        'query_cache_enable' => false,
    ],
],
```

## Troubleshooting

### Problem: Low Cache Hit Rate

**Symptoms**:
- L3 hit rate < 40%
- High database load
- Slow query response times

**Diagnosis**:
```php
$cacheStats = $perfMonitor->getCacheStats();
$invalidations = $perfMonitor->getInvalidationStats();

// Check invalidation frequency
if ($invalidations['rate'] > 100) { // per minute
    echo "Excessive invalidations detected\n";
}
```

**Solutions**:
1. Increase TTL values
2. Reduce invalidation scope (entity-level vs type-level)
3. Review write patterns

### Problem: Flat Table Out of Sync

**Symptoms**:
- Stale data in query results
- Flat table rebuild errors

**Diagnosis**:
```php
$flatStatus = $flatTableEngine->getFlatTableStatus('product');
if ($flatStatus['dirty']) {
    echo "Flat table marked dirty\n";
    echo "Last rebuild: {$flatStatus['last_rebuild']}\n";
}
```

**Solutions**:
1. Manual rebuild: `$flatTableEngine->rebuildFlatTable('product')`
2. Check sync mode configuration
3. Verify background workers are running

### Problem: Memory Exhaustion

**Symptoms**:
- PHP fatal error: Allowed memory size exhausted
- Occurs during batch operations

**Diagnosis**:
```php
$memUsage = $perfMonitor->getMemoryUsage();
echo "Peak memory: " . ($memUsage['peak'] / 1024 / 1024) . "MB\n";
```

**Solutions**:
1. Reduce batch size
2. Use chunking for large datasets
3. Clear L1 cache periodically during long operations:
   ```php
   $cacheManager->clearL1();
   ```

### Problem: Slow Batch Operations

**Symptoms**:
- Batch operations not faster than individual operations

**Diagnosis**:
```php
$batchStats = $perfMonitor->getBatchStats();
// Check if batching is actually happening
if ($batchStats['avg_batch_size'] < 10) {
    echo "Batches too small, not effective\n";
}
```

**Solutions**:
1. Increase batch size (target 100+)
2. Ensure transaction mode is enabled
3. Check flat table sync mode (use 'deferred' or 'rebuild')

## Performance Checklist

### Before Deployment

- [ ] APCu extension installed and configured
- [ ] Cache directory writable (`chmod 755` minimum)
- [ ] Flat tables created for high-traffic entities
- [ ] Indexes created on filterable attributes
- [ ] Batch sizes tuned for workload
- [ ] TTL values configured per entity type
- [ ] Monitoring enabled
- [ ] Background workers set up (if using deferred sync)

### Regular Maintenance

- [ ] Monitor cache hit rates weekly
- [ ] Review slow query log monthly
- [ ] Rebuild flat tables as needed
- [ ] Analyze entity type access patterns
- [ ] Tune TTL based on observed patterns
- [ ] Clear stale cache entries

### Performance Testing

```php
// Benchmark script
$start = microtime(true);

// Test 1: Single entity load
for ($i = 0; $i < 100; $i++) {
    $entity = $entityManager->load('product', rand(1, 1000));
}
$singleLoadTime = microtime(true) - $start;

// Test 2: Batch load
$start = microtime(true);
$ids = range(1, 100);
$entities = $batchProcessor->batchLoad('product', $ids);
$batchLoadTime = microtime(true) - $start;

// Test 3: Complex query
$start = microtime(true);
$results = $queryBuilder
    ->entityType('product')
    ->addAttributeFilter('status', 1)
    ->addAttributeFilter('price', '>', 100)
    ->limit(20)
    ->getResult();
$queryTime = microtime(true) - $start;

echo "Single load (100 entities): {$singleLoadTime}s\n";
echo "Batch load (100 entities): {$batchLoadTime}s\n";
echo "Complex query (20 results): {$queryTime}s\n";
```

**Target Benchmarks**:
- Single load average: < 15ms per entity
- Batch load average: < 2ms per entity
- Complex query: < 50ms

---

## Summary

Performance optimization in the EAV library is achieved through:

1. **Intelligent Caching**: 4-tier cache hierarchy reduces database load by 80-90%
2. **Flat Tables**: Denormalized storage for read-heavy entities provides 3-10× speedup
3. **Batch Operations**: Bulk processing reduces round trips and transaction overhead by 10-100×
4. **Continuous Monitoring**: Track metrics and tune configuration based on real usage patterns

Follow this guide to maximize performance for your specific workload characteristics.
