<?php
// app/Core/Chart/Types/LineChart.php
namespace Core\Chart\Types;

use Core\Chart\AbstractChart;

/**
 * Line Chart implementation
 * Supports multiple lines, smooth curves, and area filling
 */
class LineChart extends AbstractChart
{
    public function getType(): string
    {
        return 'line';
    }

    protected function validateSpecific(): bool
    {
        if (empty($this->data['datasets'])) {
            throw new \Core\Chart\Exception\ChartException('Line chart requires datasets');
        }

        if (empty($this->data['labels'])) {
            throw new \Core\Chart\Exception\ChartException('Line chart requires labels');
        }

        return true;
    }

    protected function renderChart(): void
    {
        $chartArea = $this->getChartArea();
        $datasets = $this->data['datasets'];
        $labels = $this->data['labels'];
        
        $this->renderGridForLine($chartArea, $labels, $datasets);
        $this->renderAxes();
        $this->renderLines($chartArea, $datasets, $labels);
    }

    protected function renderGridForLine(array $chartArea, array $labels, array $datasets): void
    {
        $labelCount = count($labels);
        $maxValue = $this->getMaxValue($datasets);
        
        // Vertical grid lines (X-axis)
        $xLines = [];
        for ($i = 0; $i <= $labelCount - 1; $i++) {
            $x = $chartArea['x'] + ($i * $chartArea['width'] / ($labelCount - 1));
            $xLines[] = $x;
        }
        
        // Horizontal grid lines (Y-axis)
        $yLines = [];
        for ($i = 0; $i <= 5; $i++) {
            $y = $chartArea['y'] + ($i * $chartArea['height'] / 5);
            $yLines[] = $y;
        }
        
        parent::renderGrid($xLines, $yLines);
        
        // Render axis labels
        $this->renderAxisLabels($chartArea, $labels, $maxValue);
    }

    private function renderLines(array $chartArea, array $datasets, array $labels): void
    {
        $labelCount = count($labels);
        $maxValue = $this->getMaxValue($datasets);
        
        foreach ($datasets as $datasetIndex => $dataset) {
            $color = $this->getColor($datasetIndex);
            $points = [];
            
            // Calculate points
            foreach ($dataset['data'] as $pointIndex => $value) {
                $x = $chartArea['x'] + ($pointIndex * $chartArea['width'] / ($labelCount - 1));
                $y = $chartArea['y'] + $chartArea['height'] - ($value / $maxValue * $chartArea['height']);
                $points[] = [$x, $y];
            }
            
            // Render area fill if enabled
            if ($dataset['fill'] ?? false) {
                $this->renderAreaFill($chartArea, $points, $color, $datasetIndex);
            }
            
            // Render line
            $this->renderLine($points, $color, $dataset, $datasetIndex);
            
            // Render points
            if ($this->config['showPoints'] ?? true) {
                $this->renderPoints($points, $color, $dataset, $datasetIndex);
            }
        }
    }

    private function renderLine(array $points, string $color, array $dataset, int $datasetIndex): void
    {
        $smooth = $dataset['smooth'] ?? $this->config['smooth'] ?? false;
        $strokeWidth = $dataset['strokeWidth'] ?? $this->config['strokeWidth'] ?? 2;
        
        if ($smooth) {
            $path = $this->createSmoothPath($points);
        } else {
            $path = $this->createLinearPath($points);
        }
        
        $this->renderer->addPath($path, [
            'stroke' => $color,
            'strokeWidth' => $strokeWidth,
            'fill' => 'none',
            'class' => "line line-{$datasetIndex}"
        ]);
    }

    private function renderPoints(array $points, string $color, array $dataset, int $datasetIndex): void
    {
        $pointRadius = $dataset['pointRadius'] ?? $this->config['pointRadius'] ?? 3;
        
        foreach ($points as $pointIndex => $point) {
            $this->renderer->addCircle($point[0], $point[1], $pointRadius, [
                'fill' => $color,
                'stroke' => '#ffffff',
                'strokeWidth' => 1,
                'class' => "point point-{$datasetIndex}-{$pointIndex}"
            ]);
            
            // Add value labels if enabled
            if ($this->config['showValues'] ?? false) {
                $value = $dataset['data'][$pointIndex];
                $this->renderer->addText(
                    $point[0],
                    $point[1] - 10,
                    (string)$value,
                    [
                        'textAnchor' => 'middle',
                        'fontSize' => $this->config['labels']['fontSize'] - 1,
                        'fill' => $this->config['labels']['color']
                    ]
                );
            }
        }
    }

    private function renderAreaFill(array $chartArea, array $points, string $color, int $datasetIndex): void
    {
        $path = $this->createLinearPath($points);
        
        // Close the path to create area
        $lastPoint = end($points);
        $firstPoint = reset($points);
        $path .= " L {$lastPoint[0]} " . ($chartArea['y'] + $chartArea['height']);
        $path .= " L {$firstPoint[0]} " . ($chartArea['y'] + $chartArea['height']);
        $path .= " Z";
        
        $fillColor = $this->adjustColorOpacity($color, 0.3);
        
        $this->renderer->addPath($path, [
            'fill' => $fillColor,
            'stroke' => 'none',
            'class' => "area area-{$datasetIndex}"
        ]);
    }

    private function createLinearPath(array $points): string
    {
        $path = "M {$points[0][0]} {$points[0][1]}";
        
        for ($i = 1; $i < count($points); $i++) {
            $path .= " L {$points[$i][0]} {$points[$i][1]}";
        }
        
        return $path;
    }

    private function createSmoothPath(array $points): string
    {
        if (count($points) < 2) {
            return $this->createLinearPath($points);
        }
        
        $path = "M {$points[0][0]} {$points[0][1]}";
        
        for ($i = 1; $i < count($points); $i++) {
            if ($i === 1) {
                $cp1x = $points[0][0] + ($points[1][0] - $points[0][0]) / 3;
                $cp1y = $points[0][1] + ($points[1][1] - $points[0][1]) / 3;
                $cp2x = $points[1][0] - ($points[1][0] - $points[0][0]) / 3;
                $cp2y = $points[1][1] - ($points[1][1] - $points[0][1]) / 3;
            } else {
                $cp1x = $points[$i-1][0] + ($points[$i][0] - $points[$i-2][0]) / 6;
                $cp1y = $points[$i-1][1] + ($points[$i][1] - $points[$i-2][1]) / 6;
                $cp2x = $points[$i][0] - ($points[$i][0] - $points[$i-1][0]) / 3;
                $cp2y = $points[$i][1] - ($points[$i][1] - $points[$i-1][1]) / 3;
            }
            
            $path .= " C {$cp1x} {$cp1y}, {$cp2x} {$cp2y}, {$points[$i][0]} {$points[$i][1]}";
        }
        
        return $path;
    }

    private function renderAxisLabels(array $chartArea, array $labels, float $maxValue): void
    {
        // X-axis labels
        foreach ($labels as $index => $label) {
            $x = $chartArea['x'] + ($index * $chartArea['width'] / (count($labels) - 1));
            $y = $chartArea['y'] + $chartArea['height'] + 20;
            
            $this->renderer->addText($x, $y, $label, [
                'textAnchor' => 'middle',
                'fontSize' => $this->config['labels']['fontSize'],
                'fill' => $this->config['labels']['color']
            ]);
        }
        
        // Y-axis labels
        for ($i = 0; $i <= 5; $i++) {
            $value = ($maxValue / 5) * $i;
            $y = $chartArea['y'] + $chartArea['height'] - ($i * $chartArea['height'] / 5);
            $x = $chartArea['x'] - 10;
            
            $this->renderer->addText($x, $y, number_format($value, 1), [
                'textAnchor' => 'end',
                'dominantBaseline' => 'middle',
                'fontSize' => $this->config['labels']['fontSize'],
                'fill' => $this->config['labels']['color']
            ]);
        }
    }

    protected function renderLegend(): void
    {
        if (!isset($this->data['datasets'])) {
            return;
        }

        $legend = $this->config['legend'];
        $legendX = $this->width - $this->config['padding']['right'] + 20;
        $legendY = $this->config['padding']['top'];
        
        foreach ($this->data['datasets'] as $index => $dataset) {
            $color = $this->getColor($index);
            $label = $dataset['label'] ?? "Dataset {$index}";
            
            // Legend line
            $this->renderer->addLine(
                $legendX,
                $legendY + ($index * 20) + 6,
                $legendX + 15,
                $legendY + ($index * 20) + 6,
                [
                    'stroke' => $color,
                    'strokeWidth' => 2
                ]
            );
            
            // Legend text
            $this->renderer->addText($legendX + 20, $legendY + ($index * 20) + 9, $label, [
                'fontSize' => $legend['fontSize'],
                'fill' => $legend['color'],
                'dominantBaseline' => 'middle'
            ]);
        }
    }

    private function getMaxValue(array $datasets): float
    {
        $max = 0;
        foreach ($datasets as $dataset) {
            $dataMax = max($dataset['data']);
            if ($dataMax > $max) {
                $max = $dataMax;
            }
        }
        return $max * 1.1; // Add 10% padding
    }

    private function adjustColorOpacity(string $color, float $opacity): string
    {
        // Convert hex to rgba for opacity
        if (strpos($color, '#') === 0) {
            $hex = ltrim($color, '#');
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            return "rgba({$r}, {$g}, {$b}, {$opacity})";
        }
        return $color;
    }
}