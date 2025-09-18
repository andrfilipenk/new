<?php
// examples/chart-controller-example.php

/**
 * Example controller demonstrating chart integration
 * Shows how to use charts in MVC controllers
 */

namespace Module\Example\Controller;

use Core\Mvc\Controller;
use Core\Chart\ChartBuilder;
use Core\Chart\ChartFactory;
use Core\Chart\Styles\ChartThemes;
use Module\Admin\Models\Users;
use Module\Admin\Models\Tasks;

class ChartController extends Controller
{
    /**
     * Display dashboard with multiple charts
     */
    public function dashboardAction(): string
    {
        /** @var ChartFactory $chartFactory */
        $chartFactory = $this->getDI()->get('chartFactory');
        
        // User registration chart
        $userChart = ChartBuilder::line()
            ->data([
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                'datasets' => [
                    [
                        'label' => 'New Users',
                        'data' => [12, 19, 15, 25, 22, 30]
                    ]
                ]
            ])
            ->title('User Registration Trend')
            ->config(ChartThemes::modern())
            ->size(800, 400)
            ->smooth()
            ->render();
        
        // Task status pie chart
        $taskChart = ChartBuilder::pie()
            ->data([
                'labels' => ['Completed', 'In Progress', 'Pending'],
                'values' => [45, 30, 25]
            ])
            ->title('Task Status Distribution')
            ->config(ChartThemes::vibrant())
            ->size(600, 400)
            ->render();
        
        return $this->render('dashboard', [
            'userChart' => $userChart,
            'taskChart' => $taskChart
        ]);
    }
    
    /**
     * API endpoint for chart data
     */
    public function chartDataAction(): \Core\Http\Response
    {
        $type = $this->getRequest()->get('type', 'bar');
        $period = $this->getRequest()->get('period', '7days');
        
        // Generate sample data based on period
        $data = $this->generateChartData($period);
        
        $chart = ChartBuilder::create($type)
            ->data($data)
            ->title("Analytics for {$period}")
            ->config(ChartThemes::modern())
            ->size(800, 600)
            ->render();
        
        return $this->response([
            'success' => true,
            'chart' => $chart,
            'data' => $data
        ]);
    }
    
    /**
     * Real-time chart with database data
     */
    public function userStatsAction(): string
    {
        // Get user statistics from database
        $users = Users::all();
        $usersByMonth = [];
        
        foreach ($users as $user) {
            $month = date('M', strtotime($user->created_at ?? 'now'));
            $usersByMonth[$month] = ($usersByMonth[$month] ?? 0) + 1;
        }
        
        $chart = ChartBuilder::bar()
            ->data([
                'labels' => array_keys($usersByMonth),
                'datasets' => [
                    [
                        'label' => 'Users Registered',
                        'data' => array_values($usersByMonth)
                    ]
                ]
            ])
            ->title('User Registration by Month')
            ->config(ChartThemes::corporate())
            ->showValues()
            ->render();
        
        return $this->render('user-stats', ['chart' => $chart]);
    }
    
    /**
     * Export chart as downloadable SVG
     */
    public function exportChartAction(): \Core\Http\Response
    {
        $config = $this->getRequest()->post('config');
        
        if (!$config) {
            return $this->response(['error' => 'No chart configuration provided'], 400);
        }
        
        try {
            $factory = $this->getDI()->get('chartFactory');
            $chart = $factory->create(json_decode($config, true));
            $svg = $chart->render();
            
            $response = new \Core\Http\Response($svg);
            $response->setHeader('Content-Type', 'image/svg+xml');
            $response->setHeader('Content-Disposition', 'attachment; filename="chart.svg"');
            
            return $response;
            
        } catch (\Exception $e) {
            return $this->response(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Interactive chart demo
     */
    public function interactiveAction(): string
    {
        $chart = ChartBuilder::bar()
            ->data([
                'labels' => ['Q1', 'Q2', 'Q3', 'Q4'],
                'datasets' => [
                    [
                        'label' => 'Revenue ($000)',
                        'data' => [150, 230, 180, 290]
                    ]
                ]
            ])
            ->title('Quarterly Revenue - Click bars for details')
            ->config(ChartThemes::modern())
            ->style('
                .bar {
                    cursor: pointer;
                    transition: all 0.3s ease;
                }
                .bar:hover {
                    fill: #ff6b6b !important;
                    transform: scale(1.05);
                }
            ')
            ->script('
                document.querySelectorAll(".bar").forEach((bar, index) => {
                    bar.addEventListener("click", function() {
                        const quarters = ["Q1", "Q2", "Q3", "Q4"];
                        const values = [150, 230, 180, 290];
                        alert(`${quarters[index]}: $${values[index]}k revenue`);
                    });
                });
            ')
            ->render();
        
        return $this->render('interactive', ['chart' => $chart]);
    }
    
    private function generateChartData(string $period): array
    {
        $days = [
            '7days' => 7,
            '30days' => 30,
            '90days' => 90
        ];
        
        $dayCount = $days[$period] ?? 7;
        $labels = [];
        $data = [];
        
        for ($i = $dayCount - 1; $i >= 0; $i--) {
            $labels[] = date('M j', strtotime("-{$i} days"));
            $data[] = rand(10, 100);
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Daily Visitors',
                    'data' => $data
                ]
            ]
        ];
    }
    
    private function response(array $data, int $status = 200): \Core\Http\Response
    {
        $response = new \Core\Http\Response();
        return $response->setStatusCode($status)->json($data);
    }
}

// Usage in routes (app/config.php):
/*
'/charts/dashboard' => [
    'controller' => 'Module\Example\Controller\ChartController',
    'action' => 'dashboard'
],
'/api/chart-data' => [
    'controller' => 'Module\Example\Controller\ChartController',
    'action' => 'chartData'
],
'/charts/export' => [
    'controller' => 'Module\Example\Controller\ChartController',
    'action' => 'exportChart'
]
*/