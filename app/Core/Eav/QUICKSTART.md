# EAV Library Phase 4 - Quick Start Guide

## Current Implementation Status

**Version**: 4.0.0 (Foundation)  
**Status**: Architecture Complete - Core Components Implemented  
**Progress**: 12% (10 of 75 planned files)

## What's Working Now

### ✅ Implemented Components

1. **Entity System** - Full entity lifecycle with dirty tracking
2. **L1 Cache** - Request-scoped caching with identity map
3. **Configuration** - Complete configuration system for all performance features
4. **Documentation** - Comprehensive guides (1,600+ lines)

### 📋 Pending Components

All other components (L2-L4 cache, flat tables, batch operations, monitoring) are architecturally designed but awaiting implementation. See `IMPLEMENTATION_STATUS.md` for details.

## Using What's Been Implemented

### 1. Entity Class

The `Entity` class provides the foundation with dirty tracking:

```php
use Core\Eav\Entity\Entity;

// Create a new entity
$product = new Entity('product');

// Set attributes (dirty tracking automatic)
$product->setAttribute('name', 'Laptop');
$product->setAttribute('sku', 'LAP-001');
$product->setAttribute('price', 999.99);

// Check what changed
$dirty = $product->getDirtyAttributes();
// Returns: ['name' => 'Laptop', 'sku' => 'LAP-001', 'price' => 999.99]

// Simulate save (mark as not new)
$product->setId(1);

// Update a value
$product->setAttribute('price', 899.99);

// Only price is dirty now
$dirty = $product->getDirtyAttributes();
// Returns: ['price' => 899.99]

// Magic property access
echo $product->name; // 'Laptop'
$product->qty = 50;  // Sets attribute

// Convert to array
$data = $product->toArray();
```

### 2. EntityType Configuration

The `EntityType` class manages entity metadata:

```php
use Core\Eav\Entity\EntityType;

// Load configuration from config file
$config = require 'app/Core/Eav/config.php';
$productConfig = $config['entity_types']['product'];

// Create entity type
$productType = new EntityType('product', $productConfig);

// Get attribute information
$attribute = $productType->getAttribute('price');
if ($attribute) {
    echo $attribute->getType();        // 'decimal'
    echo $attribute->isRequired();     // true
    echo $attribute->getBackendTable(); // 'eav_entity_decimal'
}

// Get all filterable attributes
$filterable = $productType->getFilterableAttributes();

// Check performance settings
if ($productType->isFlatTableEnabled()) {
    $syncMode = $productType->getFlatTableSyncMode(); // 'immediate'
    $cacheTtl = $productType->getCacheTtl();          // 7200 seconds
}
```

### 3. RequestCache (L1)

Request-scoped caching for current HTTP request:

```php
use Core\Eav\Cache\RequestCache;

$cache = new RequestCache();

// Store data
$cache->set('product:1', $productData);
$cache->set('product:2', $anotherProduct);

// Retrieve (within same request)
$cached = $cache->get('product:1');

// Check existence
if ($cache->has('product:1')) {
    // Cache hit
}

// Delete specific key
$cache->delete('product:1');

// Clear by prefix
$cache->clearByPrefix('product:');

// Get statistics
$stats = $cache->getStats();
/*
[
    'hits' => 15,
    'misses' => 3,
    'sets' => 18,
    'hit_rate' => 83.33,
    'size' => 5
]
*/

// Check memory usage
$bytes = $cache->getMemoryUsage();
```

### 4. IdentityMap (L1)

Ensure only one instance of each entity per request:

```php
use Core\Eav\Cache\IdentityMap;

$identityMap = new IdentityMap();

// Store entity instance
$product = new Entity('product', 1);
$identityMap->set($product);

// Retrieve same instance
$sameProduct = $identityMap->get('product', 1);
// $sameProduct === $product (same object reference)

// Check if exists
if ($identityMap->has('product', 1)) {
    // Entity already loaded
}

// Remove from map
$identityMap->remove('product', 1);

// Get all entities of a type
$products = $identityMap->getByType('product');

// Clear specific type
$identityMap->clearType('product');

// Clear all
$identityMap->clear();

// Get statistics
$stats = $identityMap->getStats();
/*
[
    'total' => 25,
    'by_type' => [
        'product' => 15,
        'customer' => 10
    ]
]
*/
```

## Configuration Examples

### Define a New Entity Type

Edit `app/Core/Eav/config.php`:

```php
'entity_types' => [
    'my_entity' => [
        'label' => 'My Entity',
        'table' => 'eav_entity',
        
        // Performance settings
        'cache_ttl' => 3600,
        'enable_flat_table' => false,
        'cache_priority' => 'normal',
        
        // Attributes
        'attributes' => [
            'title' => [
                'label' => 'Title',
                'type' => 'varchar',
                'required' => true,
                'searchable' => true,
            ],
            'value' => [
                'label' => 'Value',
                'type' => 'int',
                'required' => false,
                'filterable' => true,
            ],
        ],
    ],
],
```

### Configure Cache Layers

```php
'cache' => [
    'enable' => true,
    
    // L1 is always enabled (no config needed)
    'l1_enable' => true,
    
    // L2: Memory cache (when implemented)
    'l2_enable' => true,
    'l2_driver' => 'apcu',  // or 'static'
    'l2_ttl' => 900,
    
    // L3: Persistent cache (when implemented)
    'l3_enable' => true,
    'l3_driver' => 'file',  // or 'redis'
    'l3_ttl' => 3600,
    'l3_path' => APP_PATH . '../public/cache/eav/',
    
    // L4: Query result cache (when implemented)
    'l4_enable' => true,
    'l4_ttl' => 300,
],
```

## Typical Usage Pattern (Current)

```php
use Core\Eav\Entity\Entity;
use Core\Eav\Entity\EntityType;
use Core\Eav\Cache\IdentityMap;
use Core\Eav\Cache\RequestCache;

// Initialize for request
$identityMap = new IdentityMap();
$requestCache = new RequestCache();
$config = require 'app/Core/Eav/config.php';

// Load entity type
$productType = new EntityType('product', $config['entity_types']['product']);

// Check if entity in identity map
$product = $identityMap->get('product', 1);

if (!$product) {
    // Check request cache
    $cachedData = $requestCache->get('product:1');
    
    if ($cachedData) {
        // Hydrate from cache
        $product = new Entity('product');
        $product->fromArray($cachedData);
    } else {
        // Would load from database (when EntityManager is implemented)
        // For now, create manually
        $product = new Entity('product', 1);
        $product->setAttribute('name', 'Laptop');
        $product->setAttribute('price', 999.99);
        
        // Cache for request
        $requestCache->set('product:1', $product->toArray());
    }
    
    // Add to identity map
    $identityMap->set($product);
}

// Use entity
echo $product->name;  // 'Laptop'

// Modify
$product->setAttribute('price', 899.99);

// Check if needs saving
if ($product->isDirty()) {
    $dirtyAttrs = $product->getDirtyAttributes();
    // Would save only dirty attributes (when EntityManager is implemented)
    
    // After save
    $product->resetDirtyTracking();
}
```

## What to Expect Next

### Coming Soon (Priority Order)

1. **EntityManager** - Complete CRUD operations with L1 cache integration
2. **Database Migrations** - Create EAV table schema
3. **StorageStrategy** - EAV table read/write operations
4. **QueryBuilder** - Build and execute EAV queries
5. **L2 Cache** - APCu/static memory caching
6. **L3 Cache** - File/Redis persistent caching
7. **BatchProcessor** - Bulk insert/update/delete operations
8. **FlatTableEngine** - Denormalized table management
9. **PerformanceMonitor** - Metrics collection and profiling

### When EntityManager is Implemented

```php
// Future usage (not yet implemented)
$entityManager = $di->get(EntityManager::class);

// Create
$product = $entityManager->create('product');
$product->setAttribute('name', 'Laptop');
$entityManager->save($product);

// Load (automatically uses L1-L4 cache cascade)
$product = $entityManager->load('product', 1);

// Update (only dirty attributes saved)
$product->setAttribute('price', 899.99);
$entityManager->save($product);

// Delete
$entityManager->delete($product);
```

## Documentation

- **README.md** - Complete usage guide and API reference
- **PERFORMANCE_GUIDE.md** - Tuning and optimization strategies  
- **IMPLEMENTATION_STATUS.md** - Development roadmap and progress

## Testing Current Implementation

Create a test script:

```php
<?php
// test_eav_foundation.php

require_once 'bootstrap.php';

use Core\Eav\Entity\Entity;
use Core\Eav\Entity\EntityType;
use Core\Eav\Cache\IdentityMap;
use Core\Eav\Cache\RequestCache;

// Test Entity
$product = new Entity('product');
$product->setAttribute('name', 'Test Product');
$product->setAttribute('price', 99.99);

echo "Entity created\n";
echo "Name: " . $product->name . "\n";
echo "Dirty: " . ($product->isDirty() ? 'Yes' : 'No') . "\n\n";

// Test RequestCache
$cache = new RequestCache();
$cache->set('test', ['data' => 'value']);
$retrieved = $cache->get('test');
echo "Cache retrieved: " . json_encode($retrieved) . "\n";
echo "Cache stats: " . json_encode($cache->getStats()) . "\n\n";

// Test IdentityMap
$identityMap = new IdentityMap();
$product->setId(1);
$identityMap->set($product);
$same = $identityMap->get('product', 1);
echo "Identity map works: " . ($same === $product ? 'Yes' : 'No') . "\n";
echo "Identity stats: " . json_encode($identityMap->getStats()) . "\n\n";

// Test EntityType
$config = require 'app/Core/Eav/config.php';
$productType = new EntityType('product', $config['entity_types']['product']);
echo "Entity type: " . $productType->getLabel() . "\n";
echo "Attributes: " . count($productType->getAttributes()) . "\n";
echo "Flat table enabled: " . ($productType->isFlatTableEnabled() ? 'Yes' : 'No') . "\n";
```

## Support & Contributing

For questions about the current implementation or to contribute to completing the remaining components, refer to the task list in `IMPLEMENTATION_STATUS.md`.

The architecture is ready for the next phase of development!
