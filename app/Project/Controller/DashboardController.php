<?php

namespace Project\Controller;

use Core\Mvc\Controller;
use Project\Service\KPIService;
use Project\Service\StatisticsService;
use Project\Model\Project;

/**
 * DashboardController
 * Handles KPI dashboard with organization-wide analytics
 */
class DashboardController extends Controller
{
    protected $kpiService;
    protected $statisticsService;

    public function __construct()
    {
        parent::__construct();
        $this->kpiService = new KPIService();
        $this->statisticsService = new StatisticsService();
    }

    /**
     * KPI Dashboard view
     */
    public function kpiAction()
    {
        // Get filters from request
        $filters = [
            'date_from' => $this->request->get('date_from'),
            'date_to' => $this->request->get('date_to'),
            'status' => $this->request->get('status'),
            'department' => $this->request->get('department'),
        ];

        // Get global metrics
        $metrics = $this->kpiService->getGlobalMetrics($filters);

        // Get projects summary for grid
        $projects = Project::query();
        if (!empty($filters['status'])) {
            $projects->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $projects->where('start_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $projects->where('start_date', '<=', $filters['date_to']);
        }
        
        $projectsGrid = $projects->limit(50)->get();

        // Format chart data
        $chartData = $this->formatChartData($metrics);

        $this->view->setVar('topMetrics', $metrics['top_metrics']);
        $this->view->setVar('projectStats', $metrics['project_performance']);
        $this->view->setVar('orderStats', $metrics['order_metrics']);
        $this->view->setVar('resourceStats', $metrics['resource_utilization']);
        $this->view->setVar('financialKPIs', $metrics['financial_kpis']);
        $this->view->setVar('chartData', $chartData);
        $this->view->setVar('projectsGrid', $projectsGrid);
        $this->view->setVar('filters', $filters);
        $this->view->setVar('statuses', Project::getStatuses());

        $this->view->render('project/dashboard/kpi');
    }

    /**
     * Filter KPI data (AJAX)
     */
    public function filterAction()
    {
        if (!$this->request->isPost()) {
            $this->response->setStatusCode(405);
            return;
        }

        $filters = [
            'date_from' => $this->request->post('date_from'),
            'date_to' => $this->request->post('date_to'),
            'status' => $this->request->post('status'),
            'department' => $this->request->post('department'),
        ];

        $metrics = $this->kpiService->getGlobalMetrics($filters);
        $chartData = $this->formatChartData($metrics);

        $this->response->setJsonContent([
            'success' => true,
            'metrics' => $metrics,
            'chartData' => $chartData,
        ]);
    }

    /**
     * Export KPI data to CSV
     */
    public function exportAction()
    {
        $filters = [
            'date_from' => $this->request->get('date_from'),
            'date_to' => $this->request->get('date_to'),
            'status' => $this->request->get('status'),
        ];

        $projects = Project::query();
        if (!empty($filters['status'])) {
            $projects->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $projects->where('start_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $projects->where('start_date', '<=', $filters['date_to']);
        }
        
        $projectsData = $projects->get()->toArray();

        $columns = [
            'name' => 'Project Name',
            'code' => 'Project Code',
            'status' => 'Status',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'budget' => 'Budget',
            'priority' => 'Priority',
        ];

        $csv = $this->statisticsService->exportToCSV($projectsData, $columns);

        $this->response->setContentType('text/csv');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="kpi-export-' . date('Y-m-d') . '.csv"');
        $this->response->setContent($csv);
        $this->response->send();
    }

    /**
     * Format chart data for visualization
     *
     * @param array $metrics
     * @return array
     */
    protected function formatChartData(array $metrics): array
    {
        $chartData = [];

        // Projects by status pie chart
        if (isset($metrics['project_performance']['by_status'])) {
            $chartData['projects_by_status'] = [
                'type' => 'pie',
                'labels' => array_keys($metrics['project_performance']['by_status']),
                'data' => array_values($metrics['project_performance']['by_status']),
            ];
        }

        // Orders by status bar chart
        if (isset($metrics['order_metrics']['by_status'])) {
            $chartData['orders_by_status'] = [
                'type' => 'bar',
                'labels' => array_keys($metrics['order_metrics']['by_status']),
                'data' => array_values($metrics['order_metrics']['by_status']),
            ];
        }

        // Revenue by project
        if (isset($metrics['financial_kpis']['revenue_by_project'])) {
            $projects = $metrics['financial_kpis']['revenue_by_project'];
            $chartData['revenue_by_project'] = [
                'type' => 'horizontalBar',
                'labels' => array_map(function($p) {
                    return $p['project']->name ?? 'Unknown';
                }, $projects),
                'data' => array_map(function($p) {
                    return $p['revenue'];
                }, $projects),
            ];
        }

        // Hours by project
        if (isset($metrics['resource_utilization']['hours_by_project'])) {
            $projects = $metrics['resource_utilization']['hours_by_project'];
            $chartData['hours_by_project'] = [
                'type' => 'bar',
                'labels' => array_map(function($p) {
                    return $p['project']->name ?? 'Unknown';
                }, $projects),
                'data' => array_map(function($p) {
                    return $p['hours'];
                }, $projects),
            ];
        }

        return $chartData;
    }
}
