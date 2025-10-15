<?php

namespace Project\Service;

use Project\Model\Project;
use Project\Model\Order;
use Project\Model\Material;
use Project\Model\EmployeeActivity;

/**
 * KPIService
 * Calculate organization-wide key performance indicators
 */
class KPIService
{
    /**
     * Get global metrics across all projects
     *
     * @param array $filters
     * @return array
     */
    public function getGlobalMetrics(array $filters = []): array
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        
        return [
            'top_metrics' => $this->getTopMetrics($dateFrom, $dateTo),
            'project_performance' => $this->getProjectPerformance($dateFrom, $dateTo),
            'order_metrics' => $this->getOrderMetrics($dateFrom, $dateTo),
            'resource_utilization' => $this->getResourceUtilization($dateFrom, $dateTo),
            'financial_kpis' => $this->getFinancialKPIs($dateFrom, $dateTo),
        ];
    }

    /**
     * Get top 4 key metrics for dashboard header
     *
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    protected function getTopMetrics(?string $dateFrom, ?string $dateTo): array
    {
        $projectQuery = Project::query();
        $orderQuery = Order::query();
        
        if ($dateFrom) {
            $projectQuery->where('start_date', '>=', $dateFrom);
            $orderQuery->where('start_date', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $projectQuery->where('start_date', '<=', $dateTo);
            $orderQuery->where('start_date', '<=', $dateTo);
        }
        
        $activeProjects = (clone $projectQuery)->where('status', Project::STATUS_ACTIVE)->count();
        $totalOrders = (clone $orderQuery)->count();
        $revenue = (clone $orderQuery)->sum('total_value');
        
        // Calculate resource utilization
        $totalEmployees = EmployeeActivity::distinct('employee_id')->count('employee_id');
        $activeEmployees = EmployeeActivity::where('activity_date', '>=', date('Y-m-d', strtotime('-30 days')))
            ->distinct('employee_id')
            ->count('employee_id');
        $utilization = $totalEmployees > 0 ? ($activeEmployees / $totalEmployees) * 100 : 0;
        
        return [
            [
                'label' => 'Active Projects',
                'value' => $activeProjects,
                'trend' => 0, // Would calculate from previous period
                'type' => 'count',
            ],
            [
                'label' => 'Total Orders',
                'value' => $totalOrders,
                'trend' => 0,
                'type' => 'count',
                'breakdown' => $this->getOrderStatusBreakdown(),
            ],
            [
                'label' => 'Revenue',
                'value' => (float) $revenue,
                'trend' => 0,
                'type' => 'currency',
            ],
            [
                'label' => 'Resource Utilization',
                'value' => round($utilization, 2),
                'trend' => 0,
                'type' => 'percentage',
            ],
        ];
    }

    /**
     * Get order status breakdown
     *
     * @return array
     */
    protected function getOrderStatusBreakdown(): array
    {
        $breakdown = [];
        $statuses = Order::getStatuses();
        
        foreach ($statuses as $status) {
            $breakdown[$status] = Order::where('status', $status)->count();
        }
        
        return $breakdown;
    }

    /**
     * Get project performance metrics
     *
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public function getProjectPerformance(?string $dateFrom, ?string $dateTo): array
    {
        $query = Project::query();
        
        if ($dateFrom) {
            $query->where('start_date', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->where('start_date', '<=', $dateTo);
        }
        
        $totalProjects = $query->count();
        $completedProjects = (clone $query)->where('status', Project::STATUS_COMPLETED)->count();
        $completionRate = $totalProjects > 0 ? ($completedProjects / $totalProjects) * 100 : 0;
        
        // Projects by status
        $byStatus = [];
        foreach (Project::getStatuses() as $status) {
            $byStatus[$status] = (clone $query)->where('status', $status)->count();
        }
        
        return [
            'total_projects' => $totalProjects,
            'completed_projects' => $completedProjects,
            'completion_rate' => round($completionRate, 2),
            'by_status' => $byStatus,
        ];
    }

    /**
     * Get order metrics
     *
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public function getOrderMetrics(?string $dateFrom, ?string $dateTo): array
    {
        $query = Order::query();
        
        if ($dateFrom) {
            $query->where('start_date', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->where('start_date', '<=', $dateTo);
        }
        
        $totalOrders = $query->count();
        $totalValue = (clone $query)->sum('total_value');
        $averageValue = $totalOrders > 0 ? $totalValue / $totalOrders : 0;
        
        // On-time delivery calculation (simplified)
        $deliveredOrders = (clone $query)->where('status', Order::STATUS_DELIVERED)->count();
        $onTimeRate = $totalOrders > 0 ? ($deliveredOrders / $totalOrders) * 100 : 0;
        
        // By status
        $byStatus = [];
        foreach (Order::getStatuses() as $status) {
            $byStatus[$status] = (clone $query)->where('status', $status)->count();
        }
        
        return [
            'total_orders' => $totalOrders,
            'total_value' => (float) $totalValue,
            'average_value' => round($averageValue, 2),
            'on_time_delivery_rate' => round($onTimeRate, 2),
            'by_status' => $byStatus,
        ];
    }

    /**
     * Get resource utilization metrics
     *
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    public function getResourceUtilization(?string $dateFrom, ?string $dateTo): array
    {
        $query = EmployeeActivity::query();
        
        if ($dateFrom) {
            $query->where('activity_date', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->where('activity_date', '<=', $dateTo);
        }
        
        $totalHours = (clone $query)->sum('hours');
        $employeeCount = (clone $query)->distinct('employee_id')->count('employee_id');
        
        // Hours by project
        $hoursByProject = [];
        $activities = (clone $query)->get();
        foreach ($activities as $activity) {
            $projectId = $activity->project_id;
            if (!isset($hoursByProject[$projectId])) {
                $hoursByProject[$projectId] = [
                    'project' => $activity->project,
                    'hours' => 0,
                ];
            }
            $hoursByProject[$projectId]['hours'] += (float) $activity->hours;
        }
        
        // Material consumption
        $materialQuery = Material::query();
        if ($dateFrom) {
            $materialQuery->where('usage_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $materialQuery->where('usage_date', '<=', $dateTo);
        }
        $materialCost = $materialQuery->sum('total_cost');
        
        return [
            'total_hours' => (float) $totalHours,
            'employee_count' => $employeeCount,
            'average_hours_per_employee' => $employeeCount > 0 ? round($totalHours / $employeeCount, 2) : 0,
            'hours_by_project' => array_values($hoursByProject),
            'material_cost' => (float) $materialCost,
        ];
    }

    /**
     * Get financial KPIs
     *
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @return array
     */
    protected function getFinancialKPIs(?string $dateFrom, ?string $dateTo): array
    {
        $orderQuery = Order::query();
        
        if ($dateFrom) {
            $orderQuery->where('start_date', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $orderQuery->where('start_date', '<=', $dateTo);
        }
        
        $totalRevenue = (clone $orderQuery)->sum('total_value');
        
        // Revenue by project
        $revenueByProject = [];
        $orders = (clone $orderQuery)->get();
        foreach ($orders as $order) {
            $projectId = $order->project_id;
            if (!isset($revenueByProject[$projectId])) {
                $revenueByProject[$projectId] = [
                    'project' => $order->project,
                    'revenue' => 0,
                ];
            }
            $revenueByProject[$projectId]['revenue'] += (float) $order->total_value;
        }
        
        // Cost calculation (materials)
        $materialQuery = Material::query();
        if ($dateFrom) {
            $materialQuery->where('usage_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $materialQuery->where('usage_date', '<=', $dateTo);
        }
        $totalCost = $materialQuery->sum('total_cost');
        
        $profit = $totalRevenue - $totalCost;
        $profitMargin = $totalRevenue > 0 ? ($profit / $totalRevenue) * 100 : 0;
        
        return [
            'total_revenue' => (float) $totalRevenue,
            'total_cost' => (float) $totalCost,
            'profit' => (float) $profit,
            'profit_margin' => round($profitMargin, 2),
            'revenue_by_project' => array_values($revenueByProject),
        ];
    }
}
