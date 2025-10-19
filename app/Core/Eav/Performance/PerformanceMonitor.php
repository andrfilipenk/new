<?php

namespace App\Core\Eav\Performance;

/**
 * Performance Monitor
 * 
 * Collects and tracks EAV system performance metrics.
 * - Cache hit rates (L1-L4)
 * - Query execution times
 * - Batch operation throughput
 * - Memory usage
 * - KPI tracking
 * 
 * @package App\Core\Eav\Performance
 */
class PerformanceMonitor
{
    private array $metrics = [];
    private array $timers = [];
    private array $counters = [];
    private bool $enabled = true;
    private float $startTime;

    public function __construct()
    {
        $this->startTime = microtime(true);
    }

    /**
     * Start a timer
     * 
     * @param string $name Timer name
     */
    public function startTimer(string $name): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->timers[$name] = [
            'start' => microtime(true),
            'end' => null,
            'duration' => null,
        ];
    }

    /**
     * Stop a timer and record duration
     * 
     * @param string $name Timer name
     * @return float|null Duration in seconds
     */
    public function stopTimer(string $name): ?float
    {
        if (!$this->enabled || !isset($this->timers[$name])) {
            return null;
        }

        $this->timers[$name]['end'] = microtime(true);
        $this->timers[$name]['duration'] = 
            $this->timers[$name]['end'] - $this->timers[$name]['start'];

        return $this->timers[$name]['duration'];
    }

    /**
     * Get timer duration
     * 
     * @param string $name Timer name
     * @return float|null Duration in seconds
     */
    public function getTimer(string $name): ?float
    {
        return $this->timers[$name]['duration'] ?? null;
    }

    /**
     * Increment a counter
     * 
     * @param string $name Counter name
     * @param int $amount Amount to increment
     */
    public function increment(string $name, int $amount = 1): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!isset($this->counters[$name])) {
            $this->counters[$name] = 0;
        }

        $this->counters[$name] += $amount;
    }

    /**
     * Decrement a counter
     * 
     * @param string $name Counter name
     * @param int $amount Amount to decrement
     */
    public function decrement(string $name, int $amount = 1): void
    {
        $this->increment($name, -$amount);
    }

    /**
     * Get counter value
     * 
     * @param string $name Counter name
     * @return int Counter value
     */
    public function getCounter(string $name): int
    {
        return $this->counters[$name] ?? 0;
    }

    /**
     * Record a metric
     * 
     * @param string $name Metric name
     * @param mixed $value Metric value
     * @param array $tags Optional tags
     */
    public function record(string $name, mixed $value, array $tags = []): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!isset($this->metrics[$name])) {
            $this->metrics[$name] = [];
        }

        $this->metrics[$name][] = [
            'value' => $value,
            'timestamp' => microtime(true),
            'tags' => $tags,
        ];
    }

    /**
     * Get all metrics for a name
     * 
     * @param string $name Metric name
     * @return array Metric values
     */
    public function getMetrics(string $name): array
    {
        return $this->metrics[$name] ?? [];
    }

    /**
     * Calculate average for a metric
     * 
     * @param string $name Metric name
     * @return float|null Average value
     */
    public function getAverage(string $name): ?float
    {
        $metrics = $this->getMetrics($name);
        
        if (empty($metrics)) {
            return null;
        }

        $values = array_column($metrics, 'value');
        return array_sum($values) / count($values);
    }

    /**
     * Get minimum value for a metric
     * 
     * @param string $name Metric name
     * @return float|null Minimum value
     */
    public function getMin(string $name): ?float
    {
        $metrics = $this->getMetrics($name);
        
        if (empty($metrics)) {
            return null;
        }

        $values = array_column($metrics, 'value');
        return min($values);
    }

    /**
     * Get maximum value for a metric
     * 
     * @param string $name Metric name
     * @return float|null Maximum value
     */
    public function getMax(string $name): ?float
    {
        $metrics = $this->getMetrics($name);
        
        if (empty($metrics)) {
            return null;
        }

        $values = array_column($metrics, 'value');
        return max($values);
    }

    /**
     * Calculate percentile for a metric
     * 
     * @param string $name Metric name
     * @param float $percentile Percentile (0-100)
     * @return float|null Percentile value
     */
    public function getPercentile(string $name, float $percentile): ?float
    {
        $metrics = $this->getMetrics($name);
        
        if (empty($metrics)) {
            return null;
        }

        $values = array_column($metrics, 'value');
        sort($values);
        
        $index = ceil((count($values) * $percentile) / 100) - 1;
        $index = max(0, min($index, count($values) - 1));
        
        return $values[$index];
    }

    /**
     * Get cache hit rate
     * 
     * @param string $level Cache level (L1/L2/L3/L4)
     * @return float Hit rate percentage (0-100)
     */
    public function getCacheHitRate(string $level): float
    {
        $hits = $this->getCounter("{$level}_cache_hits");
        $misses = $this->getCounter("{$level}_cache_misses");
        $total = $hits + $misses;

        return $total > 0 ? ($hits / $total) * 100 : 0;
    }

    /**
     * Get overall cache hit rate
     * 
     * @return float Overall hit rate percentage
     */
    public function getOverallCacheHitRate(): float
    {
        $totalHits = 0;
        $totalRequests = 0;

        foreach (['L1', 'L2', 'L3', 'L4'] as $level) {
            $totalHits += $this->getCounter("{$level}_cache_hits");
            $totalRequests += $this->getCounter("{$level}_cache_hits") 
                           + $this->getCounter("{$level}_cache_misses");
        }

        return $totalRequests > 0 ? ($totalHits / $totalRequests) * 100 : 0;
    }

    /**
     * Get memory usage
     * 
     * @return array Memory statistics
     */
    public function getMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'current_formatted' => $this->formatBytes(memory_get_usage(true)),
            'peak' => memory_get_peak_usage(true),
            'peak_formatted' => $this->formatBytes(memory_get_peak_usage(true)),
            'limit' => ini_get('memory_limit'),
        ];
    }

    /**
     * Get uptime
     * 
     * @return float Uptime in seconds
     */
    public function getUptime(): float
    {
        return microtime(true) - $this->startTime;
    }

    /**
     * Generate performance report
     * 
     * @return array Comprehensive performance report
     */
    public function generateReport(): array
    {
        return [
            'uptime' => $this->getUptime(),
            'memory' => $this->getMemoryUsage(),
            'cache' => [
                'l1_hit_rate' => round($this->getCacheHitRate('L1'), 2),
                'l2_hit_rate' => round($this->getCacheHitRate('L2'), 2),
                'l3_hit_rate' => round($this->getCacheHitRate('L3'), 2),
                'l4_hit_rate' => round($this->getCacheHitRate('L4'), 2),
                'overall_hit_rate' => round($this->getOverallCacheHitRate(), 2),
            ],
            'counters' => $this->counters,
            'timers' => array_map(fn($t) => $t['duration'] ?? 0, $this->timers),
            'metrics_count' => array_map('count', $this->metrics),
        ];
    }

    /**
     * Get KPI summary
     * 
     * @return array Key performance indicators
     */
    public function getKPIs(): array
    {
        return [
            'cache_performance' => [
                'l1_hit_rate' => round($this->getCacheHitRate('L1'), 2),
                'l2_hit_rate' => round($this->getCacheHitRate('L2'), 2),
                'l3_hit_rate' => round($this->getCacheHitRate('L3'), 2),
                'l4_hit_rate' => round($this->getCacheHitRate('L4'), 2),
                'target_l1' => 80.0,
                'target_l2' => 70.0,
                'target_l3' => 60.0,
                'target_l4' => 50.0,
            ],
            'query_performance' => [
                'avg_query_time' => $this->getAverage('query_time'),
                'p95_query_time' => $this->getPercentile('query_time', 95),
                'p99_query_time' => $this->getPercentile('query_time', 99),
            ],
            'batch_performance' => [
                'batch_operations' => $this->getCounter('batch_operations'),
                'entities_processed' => $this->getCounter('batch_entities_processed'),
                'avg_batch_size' => $this->getAverage('batch_size'),
            ],
            'system' => [
                'uptime' => round($this->getUptime(), 2),
                'memory_usage' => $this->getMemoryUsage()['current_formatted'],
                'peak_memory' => $this->getMemoryUsage()['peak_formatted'],
            ],
        ];
    }

    /**
     * Export metrics to array
     * 
     * @return array All metrics data
     */
    public function export(): array
    {
        return [
            'metrics' => $this->metrics,
            'timers' => $this->timers,
            'counters' => $this->counters,
            'uptime' => $this->getUptime(),
        ];
    }

    /**
     * Reset all metrics
     */
    public function reset(): void
    {
        $this->metrics = [];
        $this->timers = [];
        $this->counters = [];
        $this->startTime = microtime(true);
    }

    /**
     * Enable performance monitoring
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable performance monitoring
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Check if monitoring is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
