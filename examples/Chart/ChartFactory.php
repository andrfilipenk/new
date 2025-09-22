<?php
// app/Core/Chart/ChartFactory.php
namespace Core\Chart;

use Core\Di\Injectable;
use Core\Chart\Exception\ChartException;

/**
 * Chart Factory for dependency injection integration
 * Following super-senior PHP practices with service pattern
 */
class ChartFactory
{
    use Injectable;

    /**
     * Create chart from configuration array
     */
    public function create(array $config): ChartInterface
    {
        $builder = new ChartBuilder();
        return $builder->fromConfig($config)->build();
    }

    /**
     * Create chart builder
     */
    public function builder(): ChartBuilder
    {
        return new ChartBuilder();
    }

    /**
     * Create chart from type
     */
    public function createChart(string $type): ChartBuilder
    {
        return ChartBuilder::create($type);
    }

    /**
     * Generate chart from database data
     */
    public function fromDatabase(string $query, array $params = [], array $config = []): string
    {
        $db         = $this->getDI()->get('db');
        $results    = $db->query($query, $params)->fetchAll();
        if (empty($results)) {
            throw new ChartException('No data returned from database query');
        }
        // Extract labels and values from results
        $labels = array_column($results, array_keys($results[0])[0]);
        $values = array_column($results, array_keys($results[0])[1]);
        $type   = $config['type'] ?? 'bar';
        $title  = $config['title'] ?? 'Database Chart';
        switch ($type) {
            case 'pie':
                return ChartBuilder::quickPie($labels, $values, $title);
            case 'line':
                return ChartBuilder::quickLine($labels, $values, $title);
            default:
                return ChartBuilder::quickBar($labels, $values, $title);
        }
    }

    /**
     * Generate chart from model data
     */
    public function fromModel(string $modelClass, string $labelField, string $valueField, array $config = []): string
    {
        $model = new $modelClass();
        $results = $model::all();
        if (empty($results)) {
            throw new ChartException('No data found in model');
        }
        $labels = [];
        $values = [];
        foreach ($results as $record) {
            $labels[] = $record->getData($labelField);
            $values[] = $record->getData($valueField);
        }
        $type = $config['type'] ?? 'bar';
        $title = $config['title'] ?? 'Model Chart';
        switch ($type) {
            case 'pie':
                return ChartBuilder::quickPie($labels, $values, $title);
            case 'line':
                return ChartBuilder::quickLine($labels, $values, $title);
            default:
                return ChartBuilder::quickBar($labels, $values, $title);
        }
    }

    /**
     * Create dashboard with multiple charts
     */
    public function dashboard(array $chartsConfig): string
    {
        $svg = '<svg width="1200" height="800" xmlns="http://www.w3.org/2000/svg">';
        $chartWidth = 580;
        $chartHeight = 380;
        $positions = [
            [10, 10],    // Top left
            [610, 10],   // Top right
            [10, 410],   // Bottom left
            [610, 410]   // Bottom right
        ];
        
        foreach ($chartsConfig as $index => $chartConfig) {
            if ($index >= 4) break; // Max 4 charts in dashboard
            $position = $positions[$index];
            $chartConfig['width'] = $chartWidth;
            $chartConfig['height'] = $chartHeight;
            $chart = $this->create($chartConfig);
            $chartSvg = $chart->render();
            // Extract content from SVG (remove svg wrapper)
            $content = preg_replace('/<svg[^>]*>/', '', $chartSvg);
            $content = str_replace('</svg>', '', $content);
            // Wrap in group with translation
            $svg .= "<g transform=\"translate({$position[0]}, {$position[1]})\">";
            $svg .= $content;
            $svg .= '</g>';
        }
        $svg .= '</svg>';
        return $svg;
    }
}