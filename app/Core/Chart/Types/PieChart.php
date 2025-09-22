<?php
// app/Core/Chart/Types/PieChart.php
namespace Core\Chart\Types;

use Core\Chart\AbstractChart;

/**
 * Pie Chart implementation
 * Supports donut charts, custom colors, and percentage labels
 */
class PieChart extends AbstractChart
{
    public function getType(): string
    {
        return 'pie';
    }

    protected function validateSpecific(): bool
    {
        if (empty($this->data['values'])) {
            throw new \Core\Chart\Exception\ChartException('Pie chart requires values array');
        }
        if (empty($this->data['labels'])) {
            throw new \Core\Chart\Exception\ChartException('Pie chart requires labels array');
        }
        if (count($this->data['values']) !== count($this->data['labels'])) {
            throw new \Core\Chart\Exception\ChartException('Values and labels arrays must have the same length');
        }
        return true;
    }

    protected function renderChart(): void
    {
        $chartArea      = $this->getChartArea();
        $values         = $this->data['values'];
        $labels         = $this->data['labels'];
        $centerX        = $chartArea['x'] + $chartArea['width'] / 2;
        $centerY        = $chartArea['y'] + $chartArea['height'] / 2;
        $radius         = min($chartArea['width'], $chartArea['height']) / 2 - 20;
        $isDonut        = $this->config['donut'] ?? false;
        $innerRadius    = $isDonut ? $radius * 0.6 : 0;
        $this->renderSlices($centerX, $centerY, $radius, $innerRadius, $values, $labels);
    }

    private function renderSlices(float $centerX, float $centerY, float $radius, float $innerRadius, array $values, array $labels): void
    {
        $total          = array_sum($values);
        $currentAngle   = -90; // Start from top
        foreach ($values as $index => $value) {
            $percentage = $value / $total;
            $angle      = $percentage * 360;
            $color      = $this->getColor($index);
            // Create slice path
            $path       = $this->createSlicePath($centerX, $centerY, $radius, $innerRadius, $currentAngle, $angle);
            $this->renderer->addPath($path, [
                'fill'          => $color,
                'stroke'        => '#ffffff',
                'strokeWidth'   => 2,
                'class'         => "slice slice-{$index}"
            ]);
            // Add percentage labels if enabled
            if ($this->config['showPercentages'] ?? true) {
                $this->renderSliceLabel($centerX, $centerY, $radius, $innerRadius, $currentAngle, $angle, $percentage);
            }
            $currentAngle += $angle;
        }
        // Add center label for donut charts
        if ($innerRadius > 0 && !empty($this->config['centerLabel'])) {
            $this->renderer->addText($centerX, $centerY, $this->config['centerLabel'], [
                'textAnchor'        => 'middle',
                'dominantBaseline'  => 'middle',
                'fontSize'          => $this->config['labels']['fontSize'] + 2,
                'fontWeight'        => 'bold',
                'fill'              => $this->config['labels']['color']
            ]);
        }
    }

    private function createSlicePath(float $centerX, float $centerY, float $radius, float $innerRadius, float $startAngle, float $angle): string
    {
        $endAngle       = $startAngle + $angle;
        $largeArcFlag   = $angle > 180 ? 1 : 0;
        // Convert degrees to radians
        $startRad       = deg2rad($startAngle);
        $endRad         = deg2rad($endAngle);
        // Calculate outer arc points
        $x1 = $centerX + $radius * cos($startRad);
        $y1 = $centerY + $radius * sin($startRad);
        $x2 = $centerX + $radius * cos($endRad);
        $y2 = $centerY + $radius * sin($endRad);
        if ($innerRadius > 0) {
            // Donut chart - create path with inner arc
            $x3 = $centerX + $innerRadius * cos($endRad);
            $y3 = $centerY + $innerRadius * sin($endRad);
            $x4 = $centerX + $innerRadius * cos($startRad);
            $y4 = $centerY + $innerRadius * sin($startRad);
            $path = "M {$x1} {$y1} ";
            $path .= "A {$radius} {$radius} 0 {$largeArcFlag} 1 {$x2} {$y2} ";
            $path .= "L {$x3} {$y3} ";
            $path .= "A {$innerRadius} {$innerRadius} 0 {$largeArcFlag} 0 {$x4} {$y4} ";
            $path .= "Z";
        } else {
            // Regular pie chart
            $path = "M {$centerX} {$centerY} ";
            $path .= "L {$x1} {$y1} ";
            $path .= "A {$radius} {$radius} 0 {$largeArcFlag} 1 {$x2} {$y2} ";
            $path .= "Z";
        }
        return $path;
    }

    private function renderSliceLabel(float $centerX, float $centerY, float $radius, float $innerRadius, float $startAngle, float $angle, float $percentage): void
    {
        $labelRadius    = $innerRadius > 0 ? ($radius + $innerRadius) / 2 : $radius * 0.7;
        $labelAngle     = $startAngle + $angle / 2;
        $labelRad       = deg2rad($labelAngle);
        $labelX         = $centerX + $labelRadius * cos($labelRad);
        $labelY         = $centerY + $labelRadius * sin($labelRad);
        $percentageText = number_format($percentage * 100, 1) . '%';
        $this->renderer->addText($labelX, $labelY, $percentageText, [
            'textAnchor'        => 'middle',
            'dominantBaseline'  => 'middle',
            'fontSize'          => $this->config['labels']['fontSize'],
            'fill'              => '#ffffff',
            'fontWeight'        => 'bold'
        ]);
    }

    protected function renderLegend(): void
    {
        if (!isset($this->data['labels'])) {
            return;
        }
        $legend     = $this->config['legend'];
        $legendX    = $this->width - $this->config['padding']['right'] + 20;
        $legendY    = $this->config['padding']['top'];
        foreach ($this->data['labels'] as $index => $label) {
            $color      = $this->getColor($index);
            $value      = $this->data['values'][$index] ?? 0;
            $total      = array_sum($this->data['values']);
            $percentage = $total > 0 ? ($value / $total) * 100 : 0;
            // Legend color box
            $this->renderer->addRect($legendX, $legendY + ($index * 20), 12, 12, [
                'fill'      => $color,
                'stroke'    => 'none'
            ]);
            // Legend text with percentage
            $legendText = $label . ' (' . number_format($percentage, 1) . '%)';
            $this->renderer->addText($legendX + 16, $legendY + ($index * 20) + 9, $legendText, [
                'fontSize'          => $legend['fontSize'],
                'fill'              => $legend['color'],
                'dominantBaseline'  => 'middle'
            ]);
        }
    }

    protected function renderAxes(): void
    {
        // Pie charts don't have axes
    }
}