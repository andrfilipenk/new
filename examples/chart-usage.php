<?php
// examples/chart-usage.php

/**
 * CHART COMPONENT USAGE EXAMPLES
 * Demonstrating the comprehensive chart generation system
 */

require_once __DIR__ . '/../app/bootstrap.php';

use Core\Chart\ChartBuilder;
use Core\Chart\ChartFactory;
use Core\Chart\Styles\ChartThemes;
use Core\Chart\Styles\CssGenerator;

echo "=== CHART COMPONENT DEMONSTRATION ===\n\n";

// Register chart service provider
$di = \Core\Di\Container::getDefault();
$chartProvider = new \Module\Provider\ChartServiceProvider();
$chartProvider->register($di);

echo "1. BASIC CHART CREATION\n";
echo "=======================\n";

// Simple bar chart
$barChart = ChartBuilder::bar()
    ->data([
        'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
        'datasets' => [
            [
                'label' => 'Sales',
                'data' => [120, 190, 300, 500, 200]
            ]
        ]
    ])
    ->title('Monthly Sales')
    ->size(800, 600)
    ->showValues()
    ->render();

echo "✓ Bar chart created with " . strlen($barChart) . " characters of SVG\n";

// Line chart with multiple series
$lineChart = ChartBuilder::line()
    ->data([
        'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
        'datasets' => [
            [
                'label' => 'Revenue',
                'data' => [1000, 1200, 800, 1500, 2000],
                'fill' => false
            ],
            [
                'label' => 'Profit',
                'data' => [200, 300, 150, 400, 600],
                'fill' => true
            ]
        ]
    ])
    ->title('Weekly Performance')
    ->smooth()
    ->showPoints()
    ->render();

echo "✓ Line chart created with smooth curves and area fill\n";

// Pie chart
$pieChart = ChartBuilder::pie()
    ->data([
        'labels' => ['Desktop', 'Mobile', 'Tablet'],
        'values' => [60, 30, 10]
    ])
    ->title('Device Usage')
    ->donut(true, 'Total: 100%')
    ->render();

echo "✓ Donut chart created with percentage labels\n\n";

echo "2. ADVANCED CONFIGURATION\n";
echo "==========================\n";

// Chart with custom theme
$themedChart = ChartBuilder::bar()
    ->data([
        'labels' => ['Q1', 'Q2', 'Q3', 'Q4'],
        'datasets' => [
            [
                'label' => 'Revenue',
                'data' => [50000, 75000, 60000, 90000]
            ]
        ]
    ])
    ->title('Quarterly Revenue')
    ->config(ChartThemes::dark())
    ->size(900, 500)
    ->orientation('horizontal')
    ->render();

echo "✓ Themed chart created with dark theme and horizontal orientation\n";

// Chart with custom styling
$styledChart = ChartBuilder::line()
    ->data([
        'labels' => ['2020', '2021', '2022', '2023', '2024'],
        'datasets' => [
            [
                'label' => 'Growth',
                'data' => [100, 150, 200, 280, 350]
            ]
        ]
    ])
    ->title('Company Growth')
    ->colors(['#667eea', '#764ba2'])
    ->grid(true, ['color' => '#f0f0f0', 'strokeWidth' => 1])
    ->padding(50, 150, 80, 100)
    ->style('
        .line { stroke-dasharray: 5, 5; }
        .point { fill: #ff6b6b; }
    ')
    ->render();

echo "✓ Custom styled chart with dashed lines and custom colors\n\n";

echo "3. QUICK CHART METHODS\n";
echo "======================\n";

// Quick charts for rapid prototyping
$quickBar = ChartBuilder::quickBar(
    ['Apple', 'Orange', 'Banana', 'Grape'],
    [45, 30, 25, 15],
    'Fruit Sales'
);

$quickLine = ChartBuilder::quickLine(
    ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
    [100, 120, 110, 140],
    'Weekly Progress'
);

$quickPie = ChartBuilder::quickPie(
    ['Chrome', 'Firefox', 'Safari', 'Edge'],
    [65, 15, 12, 8],
    'Browser Usage'
);

echo "✓ Quick bar chart: " . strlen($quickBar) . " characters\n";
echo "✓ Quick line chart: " . strlen($quickLine) . " characters\n";
echo "✓ Quick pie chart: " . strlen($quickPie) . " characters\n\n";

echo "4. FACTORY PATTERN USAGE\n";
echo "=========================\n";

/** @var ChartFactory $factory */
$factory = $di->get('chartFactory');

// Chart from configuration array
$chartConfig = [
    'type' => 'bar',
    'width' => 600,
    'height' => 400,
    'title' => 'Factory Chart',
    'data' => [
        'labels' => ['A', 'B', 'C'],
        'datasets' => [
            [
                'label' => 'Values',
                'data' => [10, 20, 15]
            ]
        ]
    ],
    'config' => ChartThemes::minimal()
];

$factoryChart = $factory->create($chartConfig);
$svgOutput = $factoryChart->render();

echo "✓ Chart created using factory pattern: " . strlen($svgOutput) . " characters\n";

// Create builder from factory
$builderChart = $factory->builder()
    ->type('pie')
    ->data([
        'labels' => ['Red', 'Blue', 'Green'],
        'values' => [40, 35, 25]
    ])
    ->title('Color Distribution')
    ->render();

echo "✓ Chart created using factory builder: " . strlen($builderChart) . " characters\n\n";

echo "5. THEMING SYSTEM\n";
echo "=================\n";

$themes = ChartThemes::all();
foreach (array_keys($themes) as $themeName) {
    $themedChart = ChartBuilder::bar()
        ->data([
            'labels' => ['Data 1', 'Data 2', 'Data 3'],
            'datasets' => [
                [
                    'label' => 'Sample',
                    'data' => [10, 15, 12]
                ]
            ]
        ])
        ->title("Theme: {$themeName}")
        ->config(ChartThemes::get($themeName))
        ->size(400, 300)
        ->render();
    
    echo "✓ {$themeName} theme chart: " . strlen($themedChart) . " characters\n";
}

echo "\n6. CSS GENERATION\n";
echo "=================\n";

$basicCss = CssGenerator::basic();
$animationCss = CssGenerator::animations();
$responsiveCss = CssGenerator::responsive();
$completeCss = CssGenerator::complete();

echo "✓ Basic CSS: " . strlen($basicCss) . " characters\n";
echo "✓ Animation CSS: " . strlen($animationCss) . " characters\n";
echo "✓ Responsive CSS: " . strlen($responsiveCss) . " characters\n";
echo "✓ Complete CSS: " . strlen($completeCss) . " characters\n\n";

echo "7. CONFIGURATION EXPORT/IMPORT\n";
echo "===============================\n";

$builder = ChartBuilder::bar()
    ->data(['labels' => ['A', 'B'], 'datasets' => [['data' => [1, 2]]]])
    ->title('Export Test')
    ->size(500, 300);

$config = $builder->toConfig();
echo "✓ Configuration exported: " . json_encode($config) . "\n";

$importedChart = ChartBuilder::create('bar')->fromConfig($config)->render();
echo "✓ Chart created from exported config: " . strlen($importedChart) . " characters\n\n";

echo "8. INTERACTIVE FEATURES\n";
echo "========================\n";

$interactiveChart = ChartBuilder::bar()
    ->data([
        'labels' => ['Product A', 'Product B', 'Product C'],
        'datasets' => [
            [
                'label' => 'Sales',
                'data' => [100, 150, 120]
            ]
        ]
    ])
    ->title('Interactive Sales Chart')
    ->script('
        // Add click handlers
        document.querySelectorAll(".bar").forEach((bar, index) => {
            bar.addEventListener("click", function() {
                alert("Clicked on bar " + (index + 1));
            });
        });
        
        // Add hover effects
        document.querySelectorAll(".bar").forEach(bar => {
            bar.addEventListener("mouseenter", function() {
                this.style.opacity = "0.8";
            });
            bar.addEventListener("mouseleave", function() {
                this.style.opacity = "1";
            });
        });
    ')
    ->style(CssGenerator::forType('bar'))
    ->render();

echo "✓ Interactive chart with JavaScript: " . strlen($interactiveChart) . " characters\n\n";

echo "=== CHART COMPONENT FEATURES ===\n";
echo "✓ Multiple chart types (Bar, Line, Pie)\n";
echo "✓ SVG rendering with CSS styling support\n";
echo "✓ Fluent API with method chaining\n";
echo "✓ Predefined themes and styling\n";
echo "✓ Factory pattern integration\n";
echo "✓ Configuration export/import\n";
echo "✓ Interactive JavaScript support\n";
echo "✓ Responsive design ready\n";
echo "✓ Animation and transition support\n";
echo "✓ Dependency injection integration\n";
echo "✓ Super-senior PHP practices\n\n";

echo "Chart component is ready for production use!\n";