<?php
// app/Admin/Controller/PerformanceController.php
namespace Admin\Controller;

use Core\Mvc\Controller;
use Core\Database\Database;
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

        // Validate date parameters
        $query = $this->getDI()->get('db')
            ->table('performance_metrics')
            ->select('performance_metrics.*, users.name as user_name')
            ->leftJoin('users', 'users.id', '=', 'performance_metrics.user_id');

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

    /**
     * Validate date format (Y-m-d)
     */
    private function isValidDate(string $date): bool
    {
        return DateTime::createFromFormat('Y-m-d', $date) !== false;
    }
}


