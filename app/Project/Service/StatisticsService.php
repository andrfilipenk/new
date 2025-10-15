<?php

namespace Project\Service;

/**
 * StatisticsService
 * Helper methods for statistical calculations and data formatting
 */
class StatisticsService
{
    /**
     * Calculate trend percentage between current and previous period
     *
     * @param float $current
     * @param float $previous
     * @return array
     */
    public function calculateTrend(float $current, float $previous): array
    {
        if ($previous == 0) {
            return [
                'percentage' => 0,
                'direction' => 'neutral',
            ];
        }
        
        $change = $current - $previous;
        $percentage = ($change / $previous) * 100;
        
        $direction = 'neutral';
        if ($percentage > 0) {
            $direction = 'up';
        } elseif ($percentage < 0) {
            $direction = 'down';
        }
        
        return [
            'percentage' => round(abs($percentage), 2),
            'direction' => $direction,
            'change' => round($change, 2),
        ];
    }

    /**
     * Aggregate entities by status field
     *
     * @param \Illuminate\Support\Collection $entities
     * @param string $statusField
     * @return array
     */
    public function aggregateByStatus($entities, string $statusField = 'status'): array
    {
        $aggregated = [];
        
        foreach ($entities as $entity) {
            $status = $entity->{$statusField};
            
            if (!isset($aggregated[$status])) {
                $aggregated[$status] = 0;
            }
            
            $aggregated[$status]++;
        }
        
        return $aggregated;
    }

    /**
     * Generate chart data structure
     *
     * @param array $data
     * @param string $labelKey
     * @param string $valueKey
     * @return array
     */
    public function generateChartData(array $data, string $labelKey, string $valueKey): array
    {
        $labels = [];
        $values = [];
        
        foreach ($data as $item) {
            $labels[] = is_array($item) ? $item[$labelKey] : $item->{$labelKey};
            $values[] = is_array($item) ? $item[$valueKey] : $item->{$valueKey};
        }
        
        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    /**
     * Export data to CSV format
     *
     * @param array $data
     * @param array $columns
     * @return string
     */
    public function exportToCSV(array $data, array $columns): string
    {
        $csv = [];
        
        // Header row
        $csv[] = implode(',', array_values($columns));
        
        // Data rows
        foreach ($data as $row) {
            $rowData = [];
            foreach (array_keys($columns) as $key) {
                $value = is_array($row) ? ($row[$key] ?? '') : ($row->{$key} ?? '');
                // Escape commas and quotes
                $value = str_replace('"', '""', $value);
                if (strpos($value, ',') !== false || strpos($value, '"') !== false) {
                    $value = '"' . $value . '"';
                }
                $rowData[] = $value;
            }
            $csv[] = implode(',', $rowData);
        }
        
        return implode("\n", $csv);
    }

    /**
     * Format currency value
     *
     * @param float $value
     * @param string $currency
     * @return string
     */
    public function formatCurrency(float $value, string $currency = 'USD'): string
    {
        return $currency . ' ' . number_format($value, 2);
    }

    /**
     * Format percentage value
     *
     * @param float $value
     * @param int $decimals
     * @return string
     */
    public function formatPercentage(float $value, int $decimals = 2): string
    {
        return number_format($value, $decimals) . '%';
    }

    /**
     * Calculate average from array of values
     *
     * @param array $values
     * @return float
     */
    public function calculateAverage(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }
        
        return round(array_sum($values) / count($values), 2);
    }

    /**
     * Group data by date period (day, week, month)
     *
     * @param \Illuminate\Support\Collection $data
     * @param string $dateField
     * @param string $period
     * @return array
     */
    public function groupByDatePeriod($data, string $dateField, string $period = 'month'): array
    {
        $grouped = [];
        
        foreach ($data as $item) {
            $date = $item->{$dateField};
            $key = $this->getDatePeriodKey($date, $period);
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            
            $grouped[$key][] = $item;
        }
        
        return $grouped;
    }

    /**
     * Get date period key for grouping
     *
     * @param string $date
     * @param string $period
     * @return string
     */
    protected function getDatePeriodKey(string $date, string $period): string
    {
        $timestamp = strtotime($date);
        
        return match($period) {
            'day' => date('Y-m-d', $timestamp),
            'week' => date('Y-W', $timestamp),
            'month' => date('Y-m', $timestamp),
            'year' => date('Y', $timestamp),
            default => date('Y-m', $timestamp),
        };
    }
}
