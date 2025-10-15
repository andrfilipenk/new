<?php

namespace Project\Service;

use Project\Model\Project;
use Project\Model\Order;
use Project\Model\EmployeeActivity;

/**
 * ProjectService
 * Business logic for project operations and statistics
 */
class ProjectService
{
    /**
     * Get project with calculated statistics
     *
     * @param int $projectId
     * @return array
     */
    public function getProjectWithStatistics(int $projectId): array
    {
        $project = Project::findOrFail($projectId);
        
        $orders = $project->orders()->get();
        $activities = $project->activities()->get();
        
        $metrics = $this->calculateProjectMetrics($projectId);
        
        return [
            'project' => $project,
            'orders' => $orders,
            'activities' => $activities,
            'metrics' => $metrics,
        ];
    }

    /**
     * Calculate project metrics
     *
     * @param int $projectId
     * @return array
     */
    public function calculateProjectMetrics(int $projectId): array
    {
        $project = Project::findOrFail($projectId);
        
        // Orders metrics
        $totalOrders = $project->orders()->count();
        $readyOrders = $project->orders()->where('status', Order::STATUS_READY)->count();
        $activeOrders = $project->orders()->where('status', Order::STATUS_IN_PROGRESS)->count();
        
        // Budget metrics
        $totalBudget = (float) $project->budget;
        $spentBudget = $this->calculateSpentBudget($projectId);
        $budgetUtilization = $totalBudget > 0 ? ($spentBudget / $totalBudget) * 100 : 0;
        
        // Team metrics
        $teamSize = $this->getTeamSize($projectId);
        $totalHours = $project->activities()->sum('hours');
        
        // Tasks metrics (if tasks table exists)
        $totalTasks = 0;
        $completedTasks = 0;
        $taskCompletion = 0;
        
        return [
            'orders' => [
                'total' => $totalOrders,
                'ready' => $readyOrders,
                'active' => $activeOrders,
            ],
            'budget' => [
                'total' => $totalBudget,
                'spent' => $spentBudget,
                'remaining' => $totalBudget - $spentBudget,
                'utilization' => round($budgetUtilization, 2),
            ],
            'team' => [
                'size' => $teamSize,
                'hours_logged' => (float) $totalHours,
            ],
            'tasks' => [
                'total' => $totalTasks,
                'completed' => $completedTasks,
                'completion' => $taskCompletion,
            ],
        ];
    }

    /**
     * Calculate spent budget from orders and materials
     *
     * @param int $projectId
     * @return float
     */
    protected function calculateSpentBudget(int $projectId): float
    {
        $project = Project::findOrFail($projectId);
        
        $ordersCost = 0;
        foreach ($project->orders as $order) {
            $materialsCost = $order->materials()->sum('total_cost');
            $ordersCost += (float) $materialsCost;
        }
        
        return $ordersCost;
    }

    /**
     * Get team size (unique employees working on project)
     *
     * @param int $projectId
     * @return int
     */
    protected function getTeamSize(int $projectId): int
    {
        return EmployeeActivity::where('project_id', $projectId)
            ->distinct('employee_id')
            ->count('employee_id');
    }

    /**
     * Get project timeline data
     *
     * @param int $projectId
     * @return array
     */
    public function getProjectTimeline(int $projectId): array
    {
        $project = Project::findOrFail($projectId);
        
        $timeline = [
            'start_date' => $project->start_date,
            'end_date' => $project->end_date,
            'orders' => [],
        ];
        
        foreach ($project->orders as $order) {
            $timeline['orders'][] = [
                'id' => $order->id,
                'title' => $order->title,
                'start_date' => $order->start_date,
                'end_date' => $order->end_date,
                'status' => $order->status,
                'phases' => $order->phases->map(function($phase) {
                    return [
                        'name' => $phase->name,
                        'start_date' => $phase->start_date,
                        'end_date' => $phase->end_date,
                        'status' => $phase->status,
                    ];
                })->toArray(),
            ];
        }
        
        return $timeline;
    }

    /**
     * List projects with filters and pagination
     *
     * @param array $filters
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function listProjectsByFilter(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $query = Project::query();
        
        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }
        
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }
        
        if (!empty($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('end_date', '<=', $filters['date_to']);
        }
        
        // Get total count
        $total = $query->count();
        
        // Apply pagination
        $offset = ($page - 1) * $perPage;
        $projects = $query->limit($perPage)->offset($offset)->get();
        
        // Calculate statistics for each project
        $projectsWithStats = [];
        foreach ($projects as $project) {
            $metrics = $this->calculateProjectMetrics($project->id);
            $projectsWithStats[] = [
                'project' => $project,
                'metrics' => $metrics,
            ];
        }
        
        return [
            'projects' => $projectsWithStats,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => ceil($total / $perPage),
            ],
        ];
    }
}
