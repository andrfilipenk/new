<?php
// app/Core/Chart/ChartBuilder.php
namespace Core\Chart;

use Core\Chart\Types\BarChart;
use Core\Chart\Types\LineChart;
use Core\Chart\Types\PieChart;
use Core\Chart\Exception\ChartException;

/**
 * Chart Builder with fluent interface for easy chart creation
 * Following super-senior PHP practices with method chaining and factory pattern
 */
class ChartBuilder
{
    private ?ChartInterface $chart = null;
    private int $width      = 800;
    private int $height     = 600;
    private array $data     = [];
    private array $config   = [];
    private string $title   = '';
    
    private const CHART_TYPES = [
        'bar'   => BarChart::class,
        'line'  => LineChart::class,
        'pie'   => PieChart::class
    ];

    /**
     * Create a new chart of specified type
     */
    public function type(string $type): self
    {
        if (!isset(self::CHART_TYPES[$type])) {
            throw new ChartException("Unsupported chart type: {$type}");
        }
        $chartClass = self::CHART_TYPES[$type];
        $this->chart = new $chartClass();
        return $this;
    }

    /**
     * Set chart data
     */
    public function data(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Set chart configuration
     */
    public function config(array $config): self
    {
        $this->config = array_merge_recursive($this->config, $config);
        return $this;
    }

    /**
     * Set chart dimensions
     */
    public function size(int $width, int $height): self
    {
        $this->width = $width;
        $this->height = $height;
        return $this;
    }

    /**
     * Set chart title
     */
    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set chart colors
     */
    public function colors(array $colors): self
    {
        $this->config['colors'] = $colors;
        return $this;
    }

    /**
     * Enable/disable grid
     */
    public function grid(bool $show = true, array $config = []): self
    {
        $this->config['grid'] = array_merge(['show' => $show], $config);
        return $this;
    }

    /**
     * Enable/disable legend
     */
    public function legend(bool $show = true, string $position = 'right'): self
    {
        $this->config['legend'] = ['show' => $show, 'position' => $position];
        return $this;
    }

    /**
     * Set padding
     */
    public function padding(int $top, int $right, int $bottom, int $left): self
    {
        $this->config['padding'] = [
            'top'       => $top,
            'right'     => $right,
            'bottom'    => $bottom,
            'left'      => $left
        ];
        return $this;
    }

    /**
     * Enable/disable value labels
     */
    public function showValues(bool $show = true): self
    {
        $this->config['showValues'] = $show;
        return $this;
    }

    /**
     * Enable/disable points for line charts
     */
    public function showPoints(bool $show = true): self
    {
        $this->config['showPoints'] = $show;
        return $this;
    }

    /**
     * Enable smooth curves for line charts
     */
    public function smooth(bool $smooth = true): self
    {
        $this->config['smooth'] = $smooth;
        return $this;
    }

    /**
     * Set chart orientation (for bar charts)
     */
    public function orientation(string $orientation): self
    {
        if (!in_array($orientation, ['vertical', 'horizontal'])) {
            throw new ChartException("Invalid orientation: {$orientation}");
        }
        $this->config['orientation'] = $orientation;
        return $this;
    }

    /**
     * Enable donut mode for pie charts
     */
    public function donut(bool $donut = true, string $centerLabel = ''): self
    {
        $this->config['donut'] = $donut;
        if (!empty($centerLabel)) {
            $this->config['centerLabel'] = $centerLabel;
        }
        return $this;
    }

    /**
     * Add CSS styling
     */
    public function style(string $css): self
    {
        if (!isset($this->config['styles'])) {
            $this->config['styles'] = [];
        }
        $this->config['styles'][] = $css;
        return $this;
    }

    /**
     * Add JavaScript interactions
     */
    public function script(string $js): self
    {
        if (!isset($this->config['scripts'])) {
            $this->config['scripts'] = [];
        }
        $this->config['scripts'][] = $js;
        return $this;
    }

    /**
     * Build and return the chart
     */
    public function build(): ChartInterface
    {
        if ($this->chart === null) {
            throw new ChartException('Chart type must be set before building');
        }
        $this->chart
            ->setData($this->data)
            ->setConfig($this->config)
            ->setDimensions($this->width, $this->height)
            ->setTitle($this->title);
        return $this->chart;
    }

    /**
     * Build and render the chart
     */
    public function render(): string
    {
        return $this->build()->render();
    }

    /**
     * Static factory method for quick chart creation
     */
    public static function create(string $type): self
    {
        return (new self())->type($type);
    }

    /**
     * Create a bar chart with fluent interface
     */
    public static function bar(): self
    {
        return self::create('bar');
    }

    /**
     * Create a line chart with fluent interface
     */
    public static function line(): self
    {
        return self::create('line');
    }

    /**
     * Create a pie chart with fluent interface
     */
    public static function pie(): self
    {
        return self::create('pie');
    }

    /**
     * Quick bar chart from simple data
     */
    public static function quickBar(array $labels, array $values, string $title = ''): string
    {
        return self::bar()
            ->data([
                'labels'    => $labels,
                'datasets'  => [[
                    'data'      => $values, 
                    'label'     => 'Values'
                    ]]
            ])
            ->title($title)
            ->render();
    }

    /**
     * Quick line chart from simple data
     */
    public static function quickLine(array $labels, array $values, string $title = ''): string
    {
        return self::line()
            ->data([
                'labels'    => $labels,
                'datasets'  => [[
                    'data'      => $values, 
                    'label'     => 'Values'
                    ]]
            ])
            ->title($title)
            ->render();
    }

    /**
     * Quick pie chart from simple data
     */
    public static function quickPie(array $labels, array $values, string $title = ''): string
    {
        return self::pie()
            ->data([
                'labels' => $labels,
                'values' => $values
            ])
            ->title($title)
            ->render();
    }

    /**
     * Load chart configuration from array
     */
    public function fromConfig(array $chartConfig): self
    {
        if (isset($chartConfig['type'])) {
            $this->type($chartConfig['type']);
        }
        if (isset($chartConfig['data'])) {
            $this->data($chartConfig['data']);
        }
        if (isset($chartConfig['width'], $chartConfig['height'])) {
            $this->size($chartConfig['width'], $chartConfig['height']);
        }
        if (isset($chartConfig['title'])) {
            $this->title($chartConfig['title']);
        }
        if (isset($chartConfig['config'])) {
            $this->config($chartConfig['config']);
        }
        return $this;
    }

    /**
     * Export chart configuration
     */
    public function toConfig(): array
    {
        return [
            'type'      => $this->chart ? $this->chart->getType() : null,
            'data'      => $this->data,
            'config'    => $this->config,
            'width'     => $this->width,
            'height'    => $this->height,
            'title'     => $this->title
        ];
    }
}