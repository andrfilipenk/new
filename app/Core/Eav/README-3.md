# EAV Phase 3: Advanced Entity Management & Query System

## Overview

The EAV (Entity-Attribute-Value) module provides a flexible data modeling solution that allows dynamic attribute management without schema changes. Phase 3 implements advanced entity management, sophisticated querying, and performance optimizations.

## Table of Contents

1. [Installation](#installation)
2. [Architecture](#architecture)
3. [Core Components](#core-components)
4. [Usage Guide](#usage-guide)
5. [API Reference](#api-reference)
6. [Performance Optimization](#performance-optimization)
7. [Examples](#examples)

---

## Installation

### 1. Run Migrations

```bash
php cli.php migrate
```

This will create all necessary EAV tables:
- `eav_entity_types` - Entity type definitions
- `eav_attributes` - Attribute definitions
- `eav_entities` - Entity instances
- `eav_values_*` - Value storage tables (varchar, int, decimal, text, datetime)
- `eav_attribute_options` - Options for select attributes
- `eav_entity_cache` - Cache storage

### 2. Register Module

Add to your bootstrap or configuration:

```php
use Eav\Module as EavModule;

$eavModule = new EavModule();
$eavModule->registerServices($di);
$eavModule->boot();
```

---

## Architecture

### System Layers

```
┌─────────────────────────────────────┐
│     Application Layer               │
│  (Controllers, Services)            │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│   Entity Management Layer           │
│  - EntityManager                    │
│  - EntityRepository                 │
│  - QueryFactory                     │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│      Query Layer                    │
│  - EavQueryBuilder                  │
│  - JoinOptimizer                    │
│  - FilterTranslator                 │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│    Data Access Layer                │
│  - ValueRepository                  │
│  - AttributeRepository              │
│  - StorageStrategy                  │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│   Performance Layer                 │
│  - CacheManager                     │
│  - BatchManager                     │
│  - IndexManager                     │
└─────────────────────────────────────┘
```

---

## Core Components

### EntityManager

Central component for entity lifecycle management.

**Responsibilities:**
- Create, read, update, delete entities
- Validate attribute values
- Manage transactions
- Fire events
- Cache invalidation

### EntityRepository

High-level repository pattern for entity operations.

**Features:**
- Fluent query interface
- Pagination support
- Bulk operations
- Search and filtering

### EavQueryBuilder

EAV-aware query builder with optimization.

**Capabilities:**
- Attribute-based filtering
- Join optimization
- Subquery strategies
- Complex WHERE conditions

### ValueRepository

Handles value storage across multiple backend types.

**Functions:**
- Multi-table value management
- Batch operations
- Type conversion
- Value validation

### CacheManager

Multi-level caching system.

**Cache Types:**
- Entity cache
- Attribute schema cache
- Query result cache
- Memory cache (runtime)

---

## Usage Guide

### 1. Define Entity Type

```php
use Eav\Models\EntityType;

$entityType = new EntityType([
    'entity_type_code' => 'product',
    'entity_type_name' => 'Product',
    'description' => 'Product catalog items',
    'is_active' => true
]);
$entityType->save();
```

### 2. Define Attributes

```php
use Eav\Models\Attribute;

$attribute = new Attribute([
    'entity_type_id' => $entityType->id,
    'attribute_code' => 'name',
    'attribute_name' => 'Product Name',
    'backend_type' => 'varchar',
    'frontend_input' => 'text',
    'is_required' => true,
    'is_searchable' => true,
    'is_filterable' => true,
    'sort_order' => 10
]);
$attribute->save();

// Numeric attribute
$priceAttr = new Attribute([
    'entity_type_id' => $entityType->id,
    'attribute_code' => 'price',
    'attribute_name' => 'Price',
    'backend_type' => 'decimal',
    'frontend_input' => 'number',
    'is_required' => true,
    'is_filterable' => true,
    'validation_rules' => ['min' => 0],
    'sort_order' => 20
]);
$priceAttr->save();
```

### 3. Create Entities

```php
use Eav\Services\EntityManager;

$entityManager = $di->get('eavEntityManager');

// Create entity with attributes
$entity = $entityManager->create($entityType->id, [
    'entity_code' => 'PROD-001',
    'name' => 'Premium Widget',
    'price' => 99.99,
    'description' => 'High-quality widget',
    'stock_quantity' => 100,
    'is_active' => true
]);

echo "Created entity ID: " . $entity->id;
```

### 4. Query Entities

```php
use Eav\Repositories\EntityRepository;

$repository = $di->get('eavEntityRepository');

// Simple query
$products = $repository->findByAttribute(
    $entityType->id,
    'name',
    'Premium Widget'
);

// Advanced query
$expensiveProducts = $repository->query($entityType->id)
    ->where('price', '>', 50)
    ->where('stock_quantity', '>', 0)
    ->orderBy('price', 'DESC')
    ->limit(10)
    ->get();

// Search with LIKE
$searchResults = $repository->searchLike(
    $entityType->id,
    'name',
    'Widget',
    20
);

// Range query
$midRangeProducts = $repository->whereBetween(
    $entityType->id,
    'price',
    25,
    75
);
```

### 5. Update Entities

```php
// Update single entity
$entityManager->update($entity->id, [
    'price' => 89.99,
    'stock_quantity' => 95
]);

// Bulk update
$repository->bulkUpdate([1, 2, 3], [
    'is_active' => false
]);
```

### 6. Batch Operations

```php
use Eav\Services\BatchManager;

$batchManager = $di->get('eavBatchManager');

// Batch create
$newEntities = [
    ['name' => 'Product 1', 'price' => 10.00],
    ['name' => 'Product 2', 'price' => 20.00],
    ['name' => 'Product 3', 'price' => 30.00],
];

$entityIds = $batchManager->batchCreate($entityType->id, $newEntities);

// Batch delete
$batchManager->batchDelete([1, 2, 3], $soft = true);
```

### 7. Pagination

```php
$page = 1;
$perPage = 20;

$result = $repository->paginate($entityType->id, $page, $perPage);

echo "Total: " . $result['total'];
echo "Page: " . $result['page'] . " of " . $result['total_pages'];

foreach ($result['data'] as $entity) {
    echo $entity->attributeValues['name'];
}
```

---

## API Reference

### EntityManager

#### create(int $entityTypeId, array $data, ?int $parentId = null): Entity
Create a new entity with attribute values.

#### find(int $entityId, bool $loadValues = true): ?Entity
Find entity by ID, optionally loading all attribute values.

#### findMany(array $entityIds, bool $loadValues = true): array
Find multiple entities by IDs.

#### update(int $entityId, array $data): bool
Update entity and its attribute values.

#### delete(int $entityId, bool $soft = true): bool
Delete entity (soft or hard delete).

#### copy(int $sourceEntityId, ?array $overrideData = null): ?Entity
Create a copy of an existing entity.

---

### EntityRepository

#### query(int $entityTypeId): EavQueryBuilder
Create query builder for entity type.

#### find(int $id, bool $loadValues = true): ?Entity
Find entity by ID.

#### search(int $entityTypeId, array $criteria, ?int $limit = null): array
Search entities by multiple criteria.

#### paginate(int $entityTypeId, int $page = 1, int $perPage = 20): array
Get paginated results.

#### firstOrCreate(int $entityTypeId, array $criteria, array $data = []): Entity
Get existing or create new entity.

---

### EavQueryBuilder

#### where(string $attributeCode, string $operator, mixed $value): self
Add WHERE condition on attribute.

#### whereIn(string $attributeCode, array $values): self
Add WHERE IN condition.

#### whereBetween(string $attributeCode, $min, $max): self
Add BETWEEN condition.

#### whereLike(string $attributeCode, string $pattern): self
Add LIKE condition.

#### orderBy(string $attributeCode, string $direction = 'ASC'): self
Add ORDER BY clause.

#### limit(int $limit): self
Set result limit.

#### offset(int $offset): self
Set result offset.

#### get(): array
Execute query and get results.

#### first(): ?Entity
Get first result.

#### count(): int
Count matching entities.

---

## Performance Optimization

### 1. Caching

The system implements multi-level caching:

```php
// Cache is automatic, but you can control TTL in config
'cache' => [
    'enabled' => true,
    'entity_ttl' => 1800,    // 30 minutes
    'schema_ttl' => 7200,    // 2 hours
    'query_ttl' => 600,      // 10 minutes
]
```

### 2. Indexing

Create indexes for searchable attributes:

```php
use Eav\Services\IndexManager;

$indexManager = $di->get('eavIndexManager');

// Create index for attribute
$indexManager->createAttributeIndex($attributeId, 'varchar');

// Rebuild all indexes for entity type
$indexManager->rebuildIndexes($entityTypeId);

// Optimize tables
$indexManager->optimizeTables();
```

### 3. Query Optimization

```php
// Select only needed attributes
$entities = $repository->withAttributes($entityTypeId, ['name', 'price']);

// Use join optimization (automatic)
// Configurable max joins
'query' => [
    'max_joins' => 10,
    'optimize_joins' => true,
]
```

### 4. Batch Processing

```php
// Process large datasets in chunks
$batchManager->setChunkSize(500);

// Batch operations are transactional
$batchManager->batchUpdateValues($updates);
```

---

## Examples

### Example 1: Product Catalog

```php
// Define product entity type
$productType = new EntityType([
    'entity_type_code' => 'product',
    'entity_type_name' => 'Product'
]);
$productType->save();

// Define attributes
$attributes = [
    ['code' => 'sku', 'type' => 'varchar', 'required' => true, 'unique' => true],
    ['code' => 'name', 'type' => 'varchar', 'required' => true, 'searchable' => true],
    ['code' => 'price', 'type' => 'decimal', 'required' => true, 'filterable' => true],
    ['code' => 'description', 'type' => 'text', 'searchable' => true],
    ['code' => 'category', 'type' => 'varchar', 'filterable' => true],
];

foreach ($attributes as $attrData) {
    $attr = new Attribute([
        'entity_type_id' => $productType->id,
        'attribute_code' => $attrData['code'],
        'attribute_name' => ucfirst($attrData['code']),
        'backend_type' => $attrData['type'],
        'is_required' => $attrData['required'] ?? false,
        'is_unique' => $attrData['unique'] ?? false,
        'is_searchable' => $attrData['searchable'] ?? false,
        'is_filterable' => $attrData['filterable'] ?? false,
    ]);
    $attr->save();
}

// Create products
$products = [
    ['sku' => 'WIDGET-001', 'name' => 'Widget A', 'price' => 29.99, 'category' => 'Electronics'],
    ['sku' => 'WIDGET-002', 'name' => 'Widget B', 'price' => 39.99, 'category' => 'Electronics'],
    ['sku' => 'GADGET-001', 'name' => 'Gadget X', 'price' => 49.99, 'category' => 'Tools'],
];

foreach ($products as $productData) {
    $entityManager->create($productType->id, $productData);
}

// Query products
$electronics = $repository->findByAttribute($productType->id, 'category', 'Electronics');

$affordable = $repository->query($productType->id)
    ->where('price', '<', 40)
    ->orderBy('price', 'ASC')
    ->get();
```

### Example 2: Customer Management

```php
// Complex query with multiple conditions
$customers = $repository->query($customerTypeId)
    ->where('status', '=', 'active')
    ->where('total_orders', '>', 10)
    ->whereBetween('last_order_date', '2024-01-01', '2024-12-31')
    ->orderBy('total_spent', 'DESC')
    ->limit(50)
    ->get();

// First or create
$customer = $repository->firstOrCreate(
    $customerTypeId,
    ['email' => 'john@example.com'],
    ['name' => 'John Doe', 'status' => 'active']
);
```

---

## Configuration

All configuration options are in `app/Eav/config.php`:

```php
return [
    'eav' => [
        'cache' => [
            'enabled' => true,
            'ttl' => 3600,
        ],
        'batch' => [
            'chunk_size' => 1000,
            'max_batch_size' => 5000,
        ],
        'query' => [
            'max_joins' => 10,
            'optimize_joins' => true,
        ],
        // ... more options
    ],
];
```

---

## Events

The system fires events for entity lifecycle:

- `eav:entity:creating` - Before entity creation
- `eav:entity:created` - After entity creation
- `eav:entity:updating` - Before entity update
- `eav:entity:updated` - After entity update
- `eav:entity:deleting` - Before entity deletion
- `eav:entity:deleted` - After entity deletion

Listen to events:

```php
$eventsManager->attach('eav:entity:created', function($event, $data) {
    // Handle event
    $entity = $data['entity'];
    // ...
});
```

---

## Best Practices

1. **Use appropriate backend types** - Choose the most specific type for performance
2. **Index searchable attributes** - Improves query performance significantly
3. **Use batch operations** - For bulk inserts/updates
4. **Enable caching** - Reduces database load
5. **Limit attribute count** - Keep queries performant (use max_joins config)
6. **Use soft deletes** - Allows recovery and maintains referential integrity

---

## Troubleshooting

### Slow Queries

1. Check index status: `$indexManager->getIndexStats($tableName)`
2. Optimize tables: `$indexManager->optimizeTables()`
3. Review join count in queries
4. Enable query caching

### Cache Issues

1. Clear cache: `$cacheManager->clear()`
2. Check cache stats: `$cacheManager->getStats()`
3. Clean expired entries: `$cacheManager->cleanExpired()`

### Orphaned Values

Clean up values without entities:
```php
$indexManager->cleanOrphanedValues();
```

---

## License

This module is part of the application framework.
