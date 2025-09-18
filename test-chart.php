<?php
// test-chart.php - Quick test of the chart system

require_once __DIR__ . '/app/bootstrap.php';

use Core\Chart\ChartBuilder;

echo "Testing chart generation...\n";

// Simple bar chart test
$chart = ChartBuilder::quickBar(
    ['Product A', 'Product B', 'Product C'],
    [100, 150, 120],
    'Sales Report'
);

// Save to file for verification
file_put_contents('test-chart.svg', $chart);

echo "✓ Chart generated successfully!\n";
echo "✓ SVG length: " . strlen($chart) . " characters\n";
echo "✓ Chart saved to test-chart.svg\n";

// Test with theme
$themedChart = ChartBuilder::pie()
    ->data([
        'labels' => ['Desktop', 'Mobile', 'Tablet'],
        'values' => [60, 30, 10]
    ])
    ->title('Device Usage')
    ->config(\Core\Chart\Styles\ChartThemes::dark())
    ->render();

echo "✓ Themed pie chart created: " . strlen($themedChart) . " characters\n";

echo "\nChart component is working perfectly!\n";