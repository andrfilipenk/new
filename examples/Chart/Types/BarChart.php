<?php
// app/Core/Chart/Types/BarChart.php
namespace Core\Chart\Types;

use Core\Chart\AbstractChart;

/**
 * Bar Chart implementation
 * Supports vertical and horizontal bar charts with multiple series
 */
class BarChart extends AbstractChart
{
    public function getType(): string
    {
        return 'bar';
    }

    protected function validateSpecific(): bool
    {
        if (empty($this->data['datasets'])) {
            throw new \Core\Chart\Exception\ChartException('Bar chart requires datasets');
        }
        if (empty($this->data['labels'])) {
            throw new \Core\Chart\Exception\ChartException('Bar chart requires labels');
        }
        return true;
    }

    protected function renderChart(): void
    {
        $chartArea  = $this->getChartArea();
        $datasets   = $this->data['datasets'];
        $labels     = $this->data['labels'];
        $isHorizontal = $this->config['orientation'] ?? 'vertical' === 'horizontal';
        if ($isHorizontal) {
            $this->renderHorizontalBars($chartArea, $datasets, $labels);
        } else {
            $this->renderVerticalBars($chartArea, $datasets, $labels);
        }
        $this->renderAxes();
    }

    private function renderVerticalBars(array $chartArea, array $datasets, array $labels): void
    {
        $labelCount     = count($labels);
        $datasetCount   = count($datasets);
        $groupWidth     = $chartArea['width'] / $labelCount;
        $barWidth       = $groupWidth / $datasetCount * 0.8; // 80% of available width
        $maxValue       = $this->getMaxValue($datasets);
        // Calculate Y scale
        $yScale         = $chartArea['height'] / $maxValue;
        // Render grid lines
        $yLines = [];
        for ($i = 0; $i <= 5; $i++) {
            $y = $chartArea['y'] + ($i * $chartArea['height'] / 5);
            $yLines[] = $y;
        }
        $this->renderGrid([], $yLines);
        // Render bars
        foreach ($datasets as $datasetIndex => $dataset) {
            $color = $this->getColor($datasetIndex);
            foreach ($dataset['data'] as $labelIndex => $value) {
                $x      = $chartArea['x'] + ($labelIndex * $groupWidth) + ($datasetIndex * $barWidth) + ($groupWidth - $datasetCount * $barWidth) / 2;
                $height = $value * $yScale;
                $y      = $chartArea['y'] + $chartArea['height'] - $height;
                $this->renderer->addRect($x, $y, $barWidth, $height, [
                    'fill'      => $color,
                    'stroke'    => 'none',
                    'class'     => "bar bar-{$datasetIndex}-{$labelIndex}"
                ]);
                // Add value labels if enabled
                if ($this->config['showValues'] ?? false) {
                    $this->renderer->addText(
                        $x + $barWidth / 2,
                        $y - 5,
                        (string)$value,
                        [
                            'textAnchor'    => 'middle',
                            'fontSize'      => $this->config['labels']['fontSize'] - 1,
                            'fill'          => $this->config['labels']['color']
                        ]
                    );
                }
            }
        }
        // Render X-axis labels
        foreach ($labels as $index => $label) {
            $x = $chartArea['x'] + ($index * $groupWidth) + ($groupWidth / 2);
            $y = $chartArea['y'] + $chartArea['height'] + 20;
            $this->renderer->addText($x, $y, $label, [
                'textAnchor'    => 'middle',
                'fontSize'      => $this->config['labels']['fontSize'],
                'fill'          => $this->config['labels']['color']
            ]);
        }
        // Render Y-axis labels
        for ($i = 0; $i <= 5; $i++) {
            $value  = ($maxValue / 5) * $i;
            $y      = $chartArea['y'] + $chartArea['height'] - ($i * $chartArea['height'] / 5);
            $x      = $chartArea['x'] - 10;
            $this->renderer->addText($x, $y, number_format($value, 1), [
                'textAnchor'        => 'end',
                'dominantBaseline'  => 'middle',
                'fontSize'          => $this->config['labels']['fontSize'],
                'fill'              => $this->config['labels']['color']
            ]);
        }
    }

    private function renderHorizontalBars(array $chartArea, array $datasets, array $labels): void
    {
        $labelCount     = count($labels);
        $datasetCount   = count($datasets);
        $groupHeight    = $chartArea['height'] / $labelCount;
        $barHeight      = $groupHeight / $datasetCount * 0.8;
        $maxValue       = $this->getMaxValue($datasets);
        // Calculate X scale
        $xScale         = $chartArea['width'] / $maxValue;
        // Render grid lines
        $xLines = [];
        for ($i = 0; $i <= 5; $i++) {
            $x = $chartArea['x'] + ($i * $chartArea['width'] / 5);
            $xLines[] = $x;
        }
        $this->renderGrid($xLines, []);
        // Render bars
        foreach ($datasets as $datasetIndex => $dataset) {
            $color = $this->getColor($datasetIndex);
            foreach ($dataset['data'] as $labelIndex => $value) {
                $y      = $chartArea['y'] + ($labelIndex * $groupHeight) + ($datasetIndex * $barHeight) + ($groupHeight - $datasetCount * $barHeight) / 2;
                $width  = $value * $xScale;
                $x      = $chartArea['x'];
                $this->renderer->addRect($x, $y, $width, $barHeight, [
                    'fill'      => $color,
                    'stroke'    => 'none',
                    'class'     => "bar bar-{$datasetIndex}-{$labelIndex}"
                ]);
                // Add value labels if enabled
                if ($this->config['showValues'] ?? false) {
                    $this->renderer->addText(
                        $x + $width + 5,
                        $y + $barHeight / 2,
                        (string)$value,
                        [
                            'dominantBaseline'  => 'middle',
                            'fontSize'          => $this->config['labels']['fontSize'] - 1,
                            'fill'              => $this->config['labels']['color']
                        ]
                    );
                }
            }
        }
        // Render Y-axis labels (categories)
        foreach ($labels as $index => $label) {
            $y = $chartArea['y'] + ($index * $groupHeight) + ($groupHeight / 2);
            $x = $chartArea['x'] - 10;
            $this->renderer->addText($x, $y, $label, [
                'textAnchor'        => 'end',
                'dominantBaseline'  => 'middle',
                'fontSize'          => $this->config['labels']['fontSize'],
                'fill'              => $this->config['labels']['color']
            ]);
        }
        // Render X-axis labels (values)
        for ($i = 0; $i <= 5; $i++) {
            $value  = ($maxValue / 5) * $i;
            $x      = $chartArea['x'] + ($i * $chartArea['width'] / 5);
            $y      = $chartArea['y'] + $chartArea['height'] + 20;
            $this->renderer->addText($x, $y, number_format($value, 1), [
                'textAnchor'    => 'middle',
                'fontSize'      => $this->config['labels']['fontSize'],
                'fill'          => $this->config['labels']['color']
            ]);
        }
    }

    protected function renderLegend(): void
    {
        if (!isset($this->data['datasets'])) {
            return;
        }
        $legend     = $this->config['legend'];
        $legendX    = $this->width - $this->config['padding']['right'] + 20;
        $legendY    = $this->config['padding']['top'];
        foreach ($this->data['datasets'] as $index => $dataset) {
            $color = $this->getColor($index);
            $label = $dataset['label'] ?? "Dataset {$index}";
            // Legend color box
            $this->renderer->addRect($legendX, $legendY + ($index * 20), 12, 12, [
                'fill'      => $color,
                'stroke'    => 'none'
            ]);
            // Legend text
            $this->renderer->addText($legendX + 16, $legendY + ($index * 20) + 9, $label, [
                'fontSize'          => $legend['fontSize'],
                'fill'              => $legend['color'],
                'dominantBaseline'  => 'middle'
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
}