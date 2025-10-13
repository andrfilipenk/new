<?php
// app/Core/Repository/PerformanceMetricsRepository.php
namespace Core\Repository;

use Core\Database\Database;

class PerformanceMetricsRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getNewMetrics(string $lastTimestamp): array
    {
        $query = $this->db->table('performance_metrics')
            ->select('performance_metrics.*, users.name as user_name')
            ->leftJoin('users', 'users.id', '=', 'performance_metrics.user_id')
            ->where('created_at', '>', $lastTimestamp)
            ->orderBy('created_at', 'asc');

        $metrics = $query->get();

        return array_map(function ($metric) {
            return [
                'user_name' => $metric->user_name ?: 'System',
                'action' => $metric->action,
                'execution_time' => number_format($metric->execution_time, 4),
                'memory_usage' => number_format($metric->memory_usage / 1024 / 1024, 2),
                'created_at' => $metric->created_at
            ];
        }, $metrics);
    }
}