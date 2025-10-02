<?php
// app/Admin/Controller/PerformanceController.php
namespace Admin\Controller;

use Core\Mvc\Controller;
use Core\Database\Database;
use Core\Http\Response;
use Core\Utils\SimplePdfGenerator;
use DateTime;

class PerformanceController extends Controller
{
    public function indexAction()
    {
        $sortBy = $this->getRequest()->get('sort_by', 'created_at');
        $sortOrder = $this->getRequest()->get('sort_order', 'desc');
        $startDate = $this->getRequest()->get('start_date');
        $endDate = $this->getRequest()->get('end_date');

        // Validate sort parameters
        $allowedColumns = ['user_name', 'action', 'execution_time', 'memory_usage', 'created_at'];
        $sortBy = in_array($sortBy, $allowedColumns) ? $sortBy : 'created_at';
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'desc';

        // Build query
        $query = $this->getDI()->get('db')
            ->table('performance_metrics')
            ->select('performance_metrics.*, users.name as user_name')
            ->leftJoin('users', 'users.id', '=', 'performance_metrics.user_id');

        // Apply date filters
        if ($startDate && $this->isValidDate($startDate)) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate && $this->isValidDate($endDate)) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        // Apply sorting
        if ($sortBy === 'user_name') {
            $query->orderBy('users.name', $sortOrder);
        } else {
            $query->orderBy('performance_metrics.' . $sortBy, $sortOrder);
        }

        $metrics = $query->get();
        return $this->render('performance/index', [
            'metrics' => $metrics,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }

    public function exportAction()
    {
        $sortBy = $this->getRequest()->get('sort_by', 'created_at');
        $sortOrder = $this->getRequest()->get('sort_order', 'desc');
        $startDate = $this->getRequest()->get('start_date');
        $endDate = $this->getRequest()->get('end_date');

        // Validate sort parameters
        $allowedColumns = ['user_name', 'action', 'execution_time', 'memory_usage', 'created_at'];
        $sortBy = in_array($sortBy, $allowedColumns) ? $sortBy : 'created_at';
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'desc';

        // Build query
        $query = $this->getDI()->get('db')
            ->table('performance_metrics')
            ->select('performance_metrics.*, users.name as user_name')
            ->leftJoin('users', 'users.id', '=', 'performance_metrics.user_id');

        // Apply date filters
        if ($startDate && $this->isValidDate($startDate)) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate && $this->isValidDate($endDate)) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        // Apply sorting
        if ($sortBy === 'user_name') {
            $query->orderBy('users.name', $sortOrder);
        } else {
            $query->orderBy('performance_metrics.' . $sortBy, $sortOrder);
        }

        $metrics = $query->get();

        // Generate CSV
        $output = fopen('php://output', 'w');
        fputcsv($output, ['User', 'Action', 'Execution Time (s)', 'Memory Usage (MB)', 'Timestamp']);
        foreach ($metrics as $metric) {
            fputcsv($output, [
                $metric->user_name ?: 'System',
                $metric->action,
                number_format($metric->execution_time, 4),
                number_format($metric->memory_usage / 1024 / 1024, 2),
                $metric->created_at
            ]);
        }
        fclose($output);

        // Set response headers
        $response = $this->getDI()->get('response');
        $response->setHeader('Content-Type', 'text/csv');
        $response->setHeader('Content-Disposition', 'attachment; filename="performance_metrics_' . date('YmdHis') . '.csv"');
        return $response;
    }

    public function pdfAction()
    {
        $sortBy = $this->getRequest()->get('sort_by', 'created_at');
        $sortOrder = $this->getRequest()->get('sort_order', 'desc');
        $startDate = $this->getRequest()->get('start_date');
        $endDate = $this->getRequest()->get('end_date');

        // Validate sort parameters
        $allowedColumns = ['user_name', 'action', 'execution_time', 'memory_usage', 'created_at'];
        $sortBy = in_array($sortBy, $allowedColumns) ? $sortBy : 'created_at';
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'desc';

        // Build query for table data
        $query = $this->getDI()->get('db')
            ->table('performance_metrics')
            ->select('performance_metrics.*, users.name as user_name')
            ->leftJoin('users', 'users.id', '=', 'performance_metrics.user_id');

        // Apply date filters
        if ($startDate && $this->isValidDate($startDate)) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate && $this->isValidDate($endDate)) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        // Apply sorting
        if ($sortBy === 'user_name') {
            $query->orderBy('users.name', $sortOrder);
        } else {
            $query->orderBy('performance_metrics.' . $sortBy, $sortOrder);
        }

        $metrics = $query->get();

        // Build query for chart data
        $chartQuery = $this->getDI()->get('db')
            ->table('performance_metrics')
            ->select('action')
            ->selectRaw('AVG(execution_time) as avg_execution_time, AVG(memory_usage) / 1024 / 1024 as avg_memory_usage')
            ->groupBy('action');

        if ($startDate && $this->isValidDate($startDate)) {
            $chartQuery->where('created_at', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate && $this->isValidDate($endDate)) {
            $chartQuery->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        $chartData = $chartQuery->get();

        // Generate PDF
        $pdf = new SimplePdfGenerator();
        $title = 'Performance Metrics Report';
        if ($startDate || $endDate) {
            $title .= ' (' . ($startDate ?: 'No Start') . ' to ' . ($endDate ?: 'No End') . ')';
        }
        $pdf->addPage($title);

        // Add charts
        $executionTimeData = array_map(function ($row) {
            return ['label' => $row->action, 'value' => number_format($row->avg_execution_time, 4)];
        }, $chartData);
        $memoryUsageData = array_map(function ($row) {
            return ['label' => $row->action, 'value' => number_format($row->avg_memory_usage, 2)];
        }, $chartData);

        $maxExecutionTime = max(array_map(fn($row) => (float)$row['value'], $executionTimeData)) ?: 1;
        $maxMemoryUsage = max(array_map(fn($row) => (float)$row['value'], $memoryUsageData)) ?: 1;

        $pdf->addBarChart('Average Execution Time per Action', $executionTimeData, 'Action', 'Time (s)', $maxExecutionTime);
        $pdf->addBarChart('Average Memory Usage per Action', $memoryUsageData, 'Action', 'Memory (MB)', $maxMemoryUsage);

        // Add table
        $pdf->addTable(
            ['User', 'Action', 'Execution Time (s)', 'Memory Usage (MB)', 'Timestamp'],
            array_map(function ($metric) {
                return [
                    $metric->user_name ?: 'System',
                    $metric->action,
                    number_format($metric->execution_time, 4),
                    number_format($metric->memory_usage / 1024 / 1024, 2),
                    $metric->created_at
                ];
            }, $metrics)
        );

        // Set response headers
        $response = $this->getDI()->get('response');
        $response->setHeader('Content-Type', 'application/pdf');
        $response->setHeader('Content-Disposition', 'attachment; filename="performance_metrics_' . date('YmdHis') . '.pdf"');
        $response->setContent($pdf->output());
        return $response;
    }

    public function chartDataAction()
    {
        $startDate = $this->getRequest()->get('start_date');
        $endDate = $this->getRequest()->get('end_date');

        // Build query for chart data
        $query = $this->getDI()->get('db')
            ->table('performance_metrics')
            ->select('action')
            ->selectRaw('AVG(execution_time) as avg_execution_time, AVG(memory_usage) / 1024 / 1024 as avg_memory_usage')
            ->groupBy('action');

        // Apply date filters
        if ($startDate && $this->isValidDate($startDate)) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate && $this->isValidDate($endDate)) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }

        $chartData = $query->get();

        // Prepare response
        $response = $this->getDI()->get('response');
        $response->setHeader('Content-Type', 'application/json');
        $response->setContent(json_encode([
            'execution_time' => array_map(function ($row) {
                return ['label' => $row->action, 'value' => number_format($row->avg_execution_time, 4)];
            }, $chartData),
            'memory_usage' => array_map(function ($row) {
                return ['label' => $row->action, 'value' => number_format($row->avg_memory_usage, 2)];
            }, $chartData)
        ]));
        return $response;
    }

    /**
     * Validate date format (Y-m-d)
     */
    private function isValidDate(string $date): bool
    {
        return DateTime::createFromFormat('Y-m-d', $date) !== false;
    }
}