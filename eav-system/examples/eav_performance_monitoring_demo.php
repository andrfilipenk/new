<?php

/**
 * EAV Performance Monitoring Demo
 * 
 * Demonstrates performance monitoring and KPI tracking capabilities.
 * Shows how to measure and optimize EAV system performance.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Eav\Performance\PerformanceMonitor;
use App\Core\Eav\Cache\CacheManager;
use App\Core\Eav\Entity\EntityManager;
use App\Core\Eav\Entity\Entity;
use App\Core\Eav\Storage\EavStorageStrategy;

// Initialize components
$monitor = new PerformanceMonitor();
$storage = new EavStorageStrategy($pdo ?? null);
$entityManager = new EntityManager($storage);
$cacheManager = new CacheManager([
    'l1_enable' => true,
    'l2_enable' => true,
    'l3_enable' => true,
    'l4_enable' => true,
]);

echo "=== EAV Performance Monitoring Demo ===\n\n";

// ===================================================================
// Demo 1: Timer Tracking
// ===================================================================
echo "Demo 1: Timer Tracking\n";
echo str_repeat("-", 50) . "\n";

// Track entity creation
$monitor->startTimer('entity_creation');
$product = new Entity('product');
$product->setAttribute('name', 'Gaming Laptop');
$product->setAttribute('price', 1299.99);
$product->setAttribute('stock', 50);
$creationTime = $monitor->stopTimer('entity_creation');

echo "Entity creation time: " . round($creationTime * 1000, 2) . "ms\n";

// Track entity save
$monitor->startTimer('entity_save');
$entityManager->save($product);
$saveTime = $monitor->stopTimer('entity_save');

echo "Entity save time: " . round($saveTime * 1000, 2) . "ms\n";

// Track entity load
$monitor->startTimer('entity_load');
$loaded = $entityManager->load('product', $product->getId());
$loadTime = $monitor->stopTimer('entity_load');

echo "Entity load time: " . round($loadTime * 1000, 2) . "ms\n\n";

// ===================================================================
// Demo 2: Counter Management
// ===================================================================
echo "Demo 2: Counter Management\n";
echo str_repeat("-", 50) . "\n";

// Simulate cache operations
for ($i = 0; $i < 100; $i++) {
    if (rand(0, 100) < 80) {
        $monitor->increment('L1_cache_hits');
    } else {
        $monitor->increment('L1_cache_misses');
    }
}

for ($i = 0; $i < 100; $i++) {
    if (rand(0, 100) < 70) {
        $monitor->increment('L2_cache_hits');
    } else {
        $monitor->increment('L2_cache_misses');
    }
}

echo "L1 Cache Hits: " . $monitor->getCounter('L1_cache_hits') . "\n";
echo "L1 Cache Misses: " . $monitor->getCounter('L1_cache_misses') . "\n";
echo "L1 Hit Rate: " . round($monitor->getCacheHitRate('L1'), 2) . "%\n\n";

echo "L2 Cache Hits: " . $monitor->getCounter('L2_cache_hits') . "\n";
echo "L2 Cache Misses: " . $monitor->getCounter('L2_cache_misses') . "\n";
echo "L2 Hit Rate: " . round($monitor->getCacheHitRate('L2'), 2) . "%\n\n";

// ===================================================================
// Demo 3: Metric Recording and Statistics
// ===================================================================
echo "Demo 3: Metric Recording and Statistics\n";
echo str_repeat("-", 50) . "\n";

// Record query execution times
$queryTimes = [0.012, 0.008, 0.015, 0.009, 0.025, 0.011, 0.007, 0.013, 0.018, 0.010];
foreach ($queryTimes as $time) {
    $monitor->record('query_time', $time, ['type' => 'select']);
}

echo "Query Statistics:\n";
echo "  Count: " . count($monitor->getMetrics('query_time')) . "\n";
echo "  Average: " . round($monitor->getAverage('query_time') * 1000, 2) . "ms\n";
echo "  Min: " . round($monitor->getMin('query_time') * 1000, 2) . "ms\n";
echo "  Max: " . round($monitor->getMax('query_time') * 1000, 2) . "ms\n";
echo "  P95: " . round($monitor->getPercentile('query_time', 95) * 1000, 2) . "ms\n";
echo "  P99: " . round($monitor->getPercentile('query_time', 99) * 1000, 2) . "ms\n\n";

// ===================================================================
// Demo 4: Cache Performance Analysis
// ===================================================================
echo "Demo 4: Cache Performance Analysis\n";
echo str_repeat("-", 50) . "\n";

// Simulate more cache operations
for ($i = 0; $i < 50; $i++) {
    if (rand(0, 100) < 65) {
        $monitor->increment('L3_cache_hits');
    } else {
        $monitor->increment('L3_cache_misses');
    }
}

for ($i = 0; $i < 50; $i++) {
    if (rand(0, 100) < 55) {
        $monitor->increment('L4_cache_hits');
    } else {
        $monitor->increment('L4_cache_misses');
    }
}

echo "All Cache Layers:\n";
echo "  L1 Hit Rate: " . round($monitor->getCacheHitRate('L1'), 2) . "% (Target: >80%)\n";
echo "  L2 Hit Rate: " . round($monitor->getCacheHitRate('L2'), 2) . "% (Target: >70%)\n";
echo "  L3 Hit Rate: " . round($monitor->getCacheHitRate('L3'), 2) . "% (Target: >60%)\n";
echo "  L4 Hit Rate: " . round($monitor->getCacheHitRate('L4'), 2) . "% (Target: >50%)\n";
echo "  Overall Hit Rate: " . round($monitor->getOverallCacheHitRate(), 2) . "%\n\n";

// ===================================================================
// Demo 5: Memory Usage Monitoring
// ===================================================================
echo "Demo 5: Memory Usage Monitoring\n";
echo str_repeat("-", 50) . "\n";

$memory = $monitor->getMemoryUsage();
echo "Current Memory: {$memory['current_formatted']}\n";
echo "Peak Memory: {$memory['peak_formatted']}\n";
echo "Memory Limit: {$memory['limit']}\n\n";

// ===================================================================
// Demo 6: KPI Dashboard
// ===================================================================
echo "Demo 6: KPI Dashboard\n";
echo str_repeat("-", 50) . "\n";

// Record batch operations
$monitor->increment('batch_operations', 5);
$monitor->increment('batch_entities_processed', 5000);
$monitor->record('batch_size', 1000);
$monitor->record('batch_size', 1200);
$monitor->record('batch_size', 800);

$kpis = $monitor->getKPIs();

echo "Cache Performance:\n";
foreach ($kpis['cache_performance'] as $key => $value) {
    if (is_numeric($value)) {
        echo "  " . ucwords(str_replace('_', ' ', $key)) . ": " . $value;
        if (strpos($key, 'rate') !== false || strpos($key, 'target') !== false) {
            echo "%";
        }
        echo "\n";
    }
}

echo "\nQuery Performance:\n";
if (isset($kpis['query_performance'])) {
    echo "  Avg Query Time: " . round(($kpis['query_performance']['avg_query_time'] ?? 0) * 1000, 2) . "ms\n";
    echo "  P95 Query Time: " . round(($kpis['query_performance']['p95_query_time'] ?? 0) * 1000, 2) . "ms\n";
    echo "  P99 Query Time: " . round(($kpis['query_performance']['p99_query_time'] ?? 0) * 1000, 2) . "ms\n";
}

echo "\nBatch Performance:\n";
if (isset($kpis['batch_performance'])) {
    echo "  Batch Operations: " . ($kpis['batch_performance']['batch_operations'] ?? 0) . "\n";
    echo "  Entities Processed: " . ($kpis['batch_performance']['entities_processed'] ?? 0) . "\n";
    echo "  Avg Batch Size: " . round($kpis['batch_performance']['avg_batch_size'] ?? 0, 0) . "\n";
}

echo "\nSystem:\n";
if (isset($kpis['system'])) {
    echo "  Uptime: " . round($kpis['system']['uptime'], 2) . "s\n";
    echo "  Memory Usage: " . ($kpis['system']['memory_usage'] ?? 'N/A') . "\n";
    echo "  Peak Memory: " . ($kpis['system']['peak_memory'] ?? 'N/A') . "\n";
}

// ===================================================================
// Demo 7: Performance Report Generation
// ===================================================================
echo "\n\nDemo 7: Performance Report Generation\n";
echo str_repeat("-", 50) . "\n";

$report = $monitor->generateReport();

echo "Performance Report:\n";
echo "  Uptime: " . round($report['uptime'], 2) . "s\n";
echo "  Memory: {$report['memory']['current_formatted']} (Peak: {$report['memory']['peak_formatted']})\n";
echo "\nCache Statistics:\n";
foreach ($report['cache'] as $layer => $hitRate) {
    echo "  " . strtoupper($layer) . ": {$hitRate}%\n";
}

echo "\nCounters:\n";
foreach ($report['counters'] as $name => $value) {
    echo "  {$name}: {$value}\n";
}

echo "\nTimers:\n";
foreach ($report['timers'] as $name => $duration) {
    echo "  {$name}: " . round($duration * 1000, 2) . "ms\n";
}

// ===================================================================
// Demo 8: Performance Optimization Recommendations
// ===================================================================
echo "\n\nDemo 8: Performance Optimization Recommendations\n";
echo str_repeat("-", 50) . "\n";

$recommendations = [];

// Check cache hit rates
if ($monitor->getCacheHitRate('L1') < 80) {
    $recommendations[] = "⚠ L1 cache hit rate below 80% target - Review entity loading patterns";
}
if ($monitor->getCacheHitRate('L2') < 70) {
    $recommendations[] = "⚠ L2 cache hit rate below 70% target - Increase APCu memory allocation";
}
if ($monitor->getCacheHitRate('L3') < 60) {
    $recommendations[] = "⚠ L3 cache hit rate below 60% target - Adjust cache TTL settings";
}

// Check query performance
$avgQueryTime = $monitor->getAverage('query_time');
if ($avgQueryTime && $avgQueryTime > 0.015) {
    $recommendations[] = "⚠ Average query time exceeds 15ms - Add database indexes";
}

// Check memory usage
$memoryPercent = ($memory['current'] / (256 * 1024 * 1024)) * 100;
if ($memoryPercent > 80) {
    $recommendations[] = "⚠ Memory usage above 80% - Optimize memory allocation or increase limit";
}

if (empty($recommendations)) {
    echo "✓ All performance metrics are within target ranges!\n";
    echo "System is performing optimally.\n";
} else {
    echo "Performance Recommendations:\n";
    foreach ($recommendations as $i => $recommendation) {
        echo ($i + 1) . ". {$recommendation}\n";
    }
}

// ===================================================================
// Demo 9: Export and Reset
// ===================================================================
echo "\n\nDemo 9: Export and Reset\n";
echo str_repeat("-", 50) . "\n";

echo "Exporting metrics for analysis...\n";
$exported = $monitor->export();
echo "Exported data contains:\n";
echo "  Metrics: " . count($exported['metrics']) . " types\n";
echo "  Timers: " . count($exported['timers']) . " timers\n";
echo "  Counters: " . count($exported['counters']) . " counters\n";
echo "  Uptime: " . round($exported['uptime'], 2) . "s\n";

echo "\nResetting all metrics...\n";
$monitor->reset();
echo "✓ Metrics reset complete\n";

echo "\n=== DEMO COMPLETE ===\n";
echo "\nKey Takeaways:\n";
echo "1. Timer tracking helps identify slow operations\n";
echo "2. Counter management enables hit rate calculation\n";
echo "3. Metric recording allows statistical analysis\n";
echo "4. KPI dashboard provides comprehensive overview\n";
echo "5. Performance reports aid in optimization\n";
echo "6. Automated recommendations guide improvements\n";
