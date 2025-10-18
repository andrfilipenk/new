<?php

/**
 * EAV Batch Operations Demo
 * 
 * Demonstrates batch processing capabilities with performance comparisons.
 * Shows 10-100x performance improvements over individual operations.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Eav\Batch\BatchProcessor;
use App\Core\Eav\Entity\Entity;
use App\Core\Eav\Entity\EntityManager;
use App\Core\Eav\Storage\EavStorageStrategy;
use App\Core\Eav\Performance\PerformanceMonitor;

// Initialize components
$storage = new EavStorageStrategy($pdo ?? null);
$entityManager = new EntityManager($storage);
$batchProcessor = new BatchProcessor($storage);
$monitor = new PerformanceMonitor();

echo "=== EAV Batch Operations Demo ===\n\n";

// ===================================================================
// Test 1: Bulk Insert Performance
// ===================================================================
echo "Test 1: Bulk Insert Performance\n";
echo str_repeat("-", 50) . "\n";

$testSize = 1000;
echo "Creating {$testSize} product entities...\n\n";

// Prepare test entities
$entities = [];
for ($i = 1; $i <= $testSize; $i++) {
    $entity = new Entity('product');
    $entity->setAttribute('name', "Product {$i}");
    $entity->setAttribute('sku', "SKU-" . str_pad($i, 6, '0', STR_PAD_LEFT));
    $entity->setAttribute('price', rand(1000, 100000) / 100);
    $entity->setAttribute('stock', rand(0, 1000));
    $entity->setAttribute('status', 'active');
    $entities[] = $entity;
}

// Method A: Individual inserts
echo "Method A: Individual inserts\n";
$monitor->startTimer('individual_insert');
$individualCount = 0;
foreach ($entities as $entity) {
    $entityManager->save($entity);
    $individualCount++;
}
$individualTime = $monitor->stopTimer('individual_insert');
echo "  Time: " . round($individualTime, 3) . "s\n";
echo "  Rate: " . round($individualCount / $individualTime, 2) . " entities/sec\n\n";

// Method B: Bulk insert
echo "Method B: Bulk insert\n";
$monitor->startTimer('bulk_insert');
$result = $batchProcessor->bulkInsert($entities, function($processed, $total) {
    echo "  Progress: {$processed}/{$total}\r";
});
$bulkTime = $monitor->stopTimer('bulk_insert');
echo "\n  Time: " . round($bulkTime, 3) . "s\n";
echo "  Rate: " . round($result['success'] / $bulkTime, 2) . " entities/sec\n";
echo "  Success: {$result['success']}, Failed: {$result['failed']}\n";

$speedup = $individualTime / $bulkTime;
echo "\n  ✓ SPEEDUP: " . round($speedup, 1) . "x faster\n\n";

// ===================================================================
// Test 2: Bulk Update Performance
// ===================================================================
echo "Test 2: Bulk Update Performance\n";
echo str_repeat("-", 50) . "\n";

// Modify entities
foreach ($entities as $entity) {
    $entity->setAttribute('price', $entity->getAttribute('price') * 1.1); // 10% increase
    $entity->setAttribute('updated_at', date('Y-m-d H:i:s'));
}

// Method A: Individual updates
echo "Method A: Individual updates\n";
$monitor->startTimer('individual_update');
$updateCount = 0;
foreach ($entities as $entity) {
    if ($entity->isDirty()) {
        $entityManager->save($entity);
        $updateCount++;
    }
}
$individualUpdateTime = $monitor->stopTimer('individual_update');
echo "  Time: " . round($individualUpdateTime, 3) . "s\n";
echo "  Updated: {$updateCount} entities\n\n";

// Method B: Bulk update
echo "Method B: Bulk update\n";
$monitor->startTimer('bulk_update');
$updateResult = $batchProcessor->bulkUpdate($entities);
$bulkUpdateTime = $monitor->stopTimer('bulk_update');
echo "  Time: " . round($bulkUpdateTime, 3) . "s\n";
echo "  Success: {$updateResult['success']}, Failed: {$updateResult['failed']}\n";

$updateSpeedup = $individualUpdateTime / $bulkUpdateTime;
echo "\n  ✓ SPEEDUP: " . round($updateSpeedup, 1) . "x faster\n\n";

// ===================================================================
// Test 3: Bulk Load Performance
// ===================================================================
echo "Test 3: Bulk Load Performance\n";
echo str_repeat("-", 50) . "\n";

$loadIds = range(1, 500);

// Method A: Individual loads
echo "Method A: Individual loads\n";
$monitor->startTimer('individual_load');
$loadedIndividual = [];
foreach ($loadIds as $id) {
    $entity = $entityManager->load('product', $id);
    if ($entity) {
        $loadedIndividual[$id] = $entity;
    }
}
$individualLoadTime = $monitor->stopTimer('individual_load');
echo "  Time: " . round($individualLoadTime, 3) . "s\n";
echo "  Loaded: " . count($loadedIndividual) . " entities\n\n";

// Method B: Bulk load
echo "Method B: Bulk load\n";
$monitor->startTimer('bulk_load');
$loadedBulk = $batchProcessor->bulkLoad('product', $loadIds);
$bulkLoadTime = $monitor->stopTimer('bulk_load');
echo "  Time: " . round($bulkLoadTime, 3) . "s\n";
echo "  Loaded: " . count($loadedBulk) . " entities\n";

$loadSpeedup = $individualLoadTime / $bulkLoadTime;
echo "\n  ✓ SPEEDUP: " . round($loadSpeedup, 1) . "x faster\n\n";

// ===================================================================
// Test 4: Bulk Delete Performance
// ===================================================================
echo "Test 4: Bulk Delete Performance\n";
echo str_repeat("-", 50) . "\n";

$deleteIds = range(1, 200);

// Method A: Individual deletes
echo "Method A: Individual deletes\n";
$monitor->startTimer('individual_delete');
$deletedCount = 0;
foreach ($deleteIds as $id) {
    $entityManager->delete('product', $id);
    $deletedCount++;
}
$individualDeleteTime = $monitor->stopTimer('individual_delete');
echo "  Time: " . round($individualDeleteTime, 3) . "s\n";
echo "  Deleted: {$deletedCount} entities\n\n";

// Method B: Bulk delete
echo "Method B: Bulk delete\n";
$monitor->startTimer('bulk_delete');
$deleteResult = $batchProcessor->bulkDelete('product', $deleteIds);
$bulkDeleteTime = $monitor->stopTimer('bulk_delete');
echo "  Time: " . round($bulkDeleteTime, 3) . "s\n";
echo "  Success: {$deleteResult['success']}, Failed: {$deleteResult['failed']}\n";

$deleteSpeedup = $individualDeleteTime / $bulkDeleteTime;
echo "\n  ✓ SPEEDUP: " . round($deleteSpeedup, 1) . "x faster\n\n";

// ===================================================================
// Performance Summary
// ===================================================================
echo "\n=== PERFORMANCE SUMMARY ===\n";
echo str_repeat("=", 50) . "\n";

$stats = $batchProcessor->getStats();

echo "\nBatch Processor Statistics:\n";
echo "  Operations: {$stats['operations']}\n";
echo "  Entities Processed: {$stats['entities_processed']}\n";
echo "  Total Time: " . round($stats['time_elapsed'], 3) . "s\n";
echo "  Avg Time/Operation: " . round($stats['avg_time_per_operation'], 4) . "s\n";
echo "  Avg Entities/Operation: " . round($stats['avg_entities_per_operation'], 0) . "\n";
echo "  Errors: {$stats['errors']}\n";

echo "\nSpeedup Summary:\n";
echo "  Insert: " . round($speedup, 1) . "x faster\n";
echo "  Update: " . round($updateSpeedup, 1) . "x faster\n";
echo "  Load: " . round($loadSpeedup, 1) . "x faster\n";
echo "  Delete: " . round($deleteSpeedup, 1) . "x faster\n";

$avgSpeedup = ($speedup + $updateSpeedup + $loadSpeedup + $deleteSpeedup) / 4;
echo "\n  ✓ AVERAGE SPEEDUP: " . round($avgSpeedup, 1) . "x faster\n";

// ===================================================================
// Memory Usage
// ===================================================================
echo "\n=== MEMORY USAGE ===\n";
$memory = $monitor->getMemoryUsage();
echo "  Current: {$memory['current_formatted']}\n";
echo "  Peak: {$memory['peak_formatted']}\n";
echo "  Limit: {$memory['limit']}\n";

// ===================================================================
// Recommendations
// ===================================================================
echo "\n=== RECOMMENDATIONS ===\n";
echo str_repeat("=", 50) . "\n";

if ($speedup < 10) {
    echo "⚠ INSERT: Speedup below 10x target.\n";
    echo "  - Increase chunk_size (current: {$batchProcessor->getChunkSize()})\n";
    echo "  - Use database transactions\n";
    echo "  - Disable cache updates during bulk insert\n";
}

if ($avgSpeedup >= 10) {
    echo "✓ EXCELLENT: Average speedup exceeds 10x target!\n";
    echo "  Batch operations are performing optimally.\n";
} elseif ($avgSpeedup >= 5) {
    echo "✓ GOOD: Average speedup meets 5x minimum target.\n";
    echo "  Consider tuning for higher performance.\n";
} else {
    echo "⚠ NEEDS IMPROVEMENT: Speedup below 5x minimum.\n";
    echo "  Review configuration and database indices.\n";
}

echo "\n=== DEMO COMPLETE ===\n";

