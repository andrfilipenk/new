<?php
/**
 * EAV Library - Cache Demo
 * 
 * Demonstrates L1 cache (Request Cache + Identity Map) with Entity Manager
 */

require_once __DIR__ . '/../bootstrap.php';

use Core\Eav\Entity\EntityManager;
use Core\Eav\Storage\EavStorageStrategy;

echo "=== EAV Library Phase 4 - Cache Demo ===\n\n";

// Create storage and entity manager
$storage = new EavStorageStrategy();
$entityManager = new EntityManager($storage);

echo "1. Creating Product Entity\n";
echo str_repeat("-", 50) . "\n";

// Create new product
$product = $entityManager->create('product');
$product->setAttribute('name', 'Gaming Laptop');
$product->setAttribute('sku', 'LAP-GAMING-001');
$product->setAttribute('price', 1299.99);
$product->setAttribute('qty', 25);
$product->setAttribute('status', 1);

echo "Product created with attributes:\n";
echo "  Name: {$product->name}\n";
echo "  SKU: {$product->sku}\n";
echo "  Price: \${$product->price}\n";
echo "  Quantity: {$product->qty}\n";
echo "  Is New: " . ($product->isNew() ? 'Yes' : 'No') . "\n";
echo "  Is Dirty: " . ($product->isDirty() ? 'Yes' : 'No') . "\n\n";

echo "2. Demonstrating Dirty Tracking\n";
echo str_repeat("-", 50) . "\n";

// Simulate save (would persist to database)
$product->setId(1); // Simulate database ID assignment
$product->resetDirtyTracking();

echo "After 'save' - Is Dirty: " . ($product->isDirty() ? 'Yes' : 'No') . "\n";

// Update price
$product->setAttribute('price', 1199.99);

echo "Updated price to \$1199.99\n";
echo "Is Dirty: " . ($product->isDirty() ? 'Yes' : 'No') . "\n";
echo "Dirty Attributes: " . json_encode($product->getDirtyAttributes()) . "\n\n";

echo "3. Testing L1 Cache - Request Cache\n";
echo str_repeat("-", 50) . "\n";

$requestCache = $entityManager->getRequestCache();

// Simulate caching entity
$cacheKey = "product:1";
$requestCache->set($cacheKey, $product->toArray());

echo "Cached entity with key: $cacheKey\n";

// Retrieve from cache
$cachedData = $requestCache->get($cacheKey);
echo "Retrieved from cache: " . ($cachedData ? 'Yes' : 'No') . "\n";

// Get cache statistics
$cacheStats = $requestCache->getStats();
echo "Cache Stats:\n";
echo "  Hits: {$cacheStats['hits']}\n";
echo "  Misses: {$cacheStats['misses']}\n";
echo "  Hit Rate: {$cacheStats['hit_rate']}%\n";
echo "  Cache Size: {$cacheStats['size']} entries\n\n";

echo "4. Testing Identity Map\n";
echo str_repeat("-", 50) . "\n";

$identityMap = $entityManager->getIdentityMap();

// Store in identity map
$identityMap->set($product);

echo "Stored product in identity map\n";

// Retrieve same instance
$sameProduct = $identityMap->get('product', 1);
echo "Retrieved from identity map: " . ($sameProduct === $product ? 'Same instance' : 'Different instance') . "\n";

// Identity map statistics
$identityStats = $identityMap->getStats();
echo "Identity Map Stats:\n";
echo "  Total Entities: {$identityStats['total']}\n";
echo "  By Type: " . json_encode($identityStats['by_type']) . "\n\n";

echo "5. Creating Multiple Entities\n";
echo str_repeat("-", 50) . "\n";

for ($i = 2; $i <= 5; $i++) {
    $p = $entityManager->create('product');
    $p->setId($i);
    $p->setAttribute('name', "Product $i");
    $p->setAttribute('sku', "SKU-$i");
    $p->setAttribute('price', rand(100, 1000));
    $identityMap->set($p);
    echo "Created and cached Product $i\n";
}

echo "\n6. Identity Map After Multiple Entities\n";
echo str_repeat("-", 50) . "\n";

$stats = $identityMap->getStats();
echo "Total Entities in Identity Map: {$stats['total']}\n";
echo "Products: {$stats['by_type']['product']}\n\n";

// Retrieve all products from identity map
$products = $identityMap->getByType('product');
echo "Retrieved {count($products)} products from identity map:\n";
foreach ($products as $p) {
    echo "  - {$p->name} (SKU: {$p->sku}, Price: \${$p->getAttribute('price')})\n";
}

echo "\n7. Cache Performance Simulation\n";
echo str_repeat("-", 50) . "\n";

// Simulate multiple cache lookups
$lookups = 100;
for ($i = 0; $i < $lookups; $i++) {
    $id = rand(1, 10);
    $key = "product:$id";
    
    // Simulate cache lookup
    if (rand(0, 1)) {
        $requestCache->set($key, ['id' => $id, 'name' => "Product $id"]);
    }
    $requestCache->get($key);
}

$finalStats = $requestCache->getStats();
echo "After $lookups lookups:\n";
echo "  Hits: {$finalStats['hits']}\n";
echo "  Misses: {$finalStats['misses']}\n";
echo "  Hit Rate: {$finalStats['hit_rate']}%\n";
echo "  Total Sets: {$finalStats['sets']}\n";
echo "  Cache Size: {$finalStats['size']} entries\n";
echo "  Memory Usage: ~" . round($requestCache->getMemoryUsage() / 1024, 2) . " KB\n\n";

echo "8. Cache Manager Statistics\n";
echo str_repeat("-", 50) . "\n";

$allStats = $entityManager->getCacheStats();
echo "Request Cache:\n";
echo "  Hit Rate: {$allStats['request_cache']['hit_rate']}%\n";
echo "  Total Operations: " . ($allStats['request_cache']['hits'] + $allStats['request_cache']['misses']) . "\n";

echo "\nIdentity Map:\n";
echo "  Total Entities: {$allStats['identity_map']['total']}\n";
echo "  By Type: " . json_encode($allStats['identity_map']['by_type']) . "\n\n";

echo "9. Clearing Caches\n";
echo str_repeat("-", 50) . "\n";

echo "Clearing cache for product type...\n";
$entityManager->clearCacheForType('product');

$statsAfterClear = $entityManager->getIdentityMap()->getStats();
echo "Identity Map after clear: {$statsAfterClear['total']} entities\n\n";

echo "=== Demo Complete ===\n";
echo "\nKey Takeaways:\n";
echo "✓ Entity class provides dirty tracking for optimized updates\n";
echo "✓ Request Cache (L1) provides fast in-memory caching\n";
echo "✓ Identity Map ensures single entity instance per request\n";
echo "✓ Entity Manager integrates all caching automatically\n";
echo "✓ Cache statistics available for monitoring\n";
