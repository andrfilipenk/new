<?php
// app/Core/Chart/AbstractChart.php
namespace Core\Chart;

use Core\Chart\Renderer\SvgRenderer;
use Core\Chart\Exception\ChartException;

/**
 * Abstract base chart class implementing common functionality
 * Following super-senior PHP practices with strict typing and interface segregation
 */
abstract class AbstractChart implements ChartInterface
{
    protected array $data = [];
    protected array $config = [];
    protected int $width = 800;
    protected int $height = 600;
    protected string $title = '';
    protected SvgRenderer $renderer;
    
    // Default configuration
    protected array $defaultConfig = [
        'colors' => ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c'],
        'background' => '#ffffff',
        'grid' => [
            'show' => true,
            'color' => '#e0e0e0',
            'strokeWidth' => 1
        ],
        'axis' => [
            'show' => true,
            'color' => '#333333',
            'strokeWidth' => 2
        ],
        'labels' => [
            'show' => true,
            'color' => '#333333',
            'fontSize' => 12,
            'fontFamily' => 'Arial, sans-serif'
        ],
        'legend' => [
            'show' => true,
            'position' => 'right',
            'color' => '#333333',
            'fontSize' => 11
        ],
        'padding' => [
            'top' => 40,
            'right' => 100,
            'bottom' => 60,
            'left' => 80
        ]
    ];

    public function __construct(SvgRenderer $renderer = null)
    {
        $this->renderer = $renderer ?? new SvgRenderer();
        $this->config = $this->defaultConfig;
    }

    public function setData(array $data): ChartInterface
    {
        $this->data = $data;
        return $this;
    }

    public function setConfig(array $config): ChartInterface
    {
        $this->config = $this->mergeConfig($this->config, $config);
        return $this;
    }

    public function setDimensions(int $width, int $height): ChartInterface
    {
        $this->width = $width;
        $this->height = $height;
        return $this;
    }

    public function setTitle(string $title): ChartInterface
    {
        $this->title = $title;
        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function validate(): bool
    {
        if (empty($this->data)) {
            throw new ChartException('Chart data cannot be empty');
        }

        if ($this->width <= 0 || $this->height <= 0) {
            throw new ChartException('Chart dimensions must be positive');
        }

        return $this->validateSpecific();
    }

    public function render(): string
    {
        $this->validate();
        
        $this->renderer->setDimensions($this->width, $this->height);
        $this->renderer->setBackground($this->config['background'] ?? '#ffffff');
        
        // Add title
        if (!empty($this->title)) {
            $this->renderTitle();
        }

        // Render chart-specific content
        $this->renderChart();

        // Add legend if enabled
        if ($this->config['legend']['show']) {
            $this->renderLegend();
        }

        return $this->renderer->render();
    }

    /**
     * Calculate chart area dimensions considering padding
     */
    protected function getChartArea(): array
    {
        $padding = $this->config['padding'];
        
        return [
            'x' => $padding['left'],
            'y' => $padding['top'],
            'width' => $this->width - $padding['left'] - $padding['right'],
            'height' => $this->height - $padding['top'] - $padding['bottom']
        ];
    }

    /**
     * Get color by index with cycling
     */
    protected function getColor(int $index): string
    {
        $colors = $this->config['colors'];
        return $colors[$index % count($colors)];
    }

    /**
     * Render chart title
     */
    protected function renderTitle(): void
    {
        $titleY = $this->config['padding']['top'] / 2;
        $titleX = $this->width / 2;
        
        $this->renderer->addText(
            $titleX,
            $titleY,
            $this->title,
            [
                'textAnchor' => 'middle',
                'fontSize' => $this->config['labels']['fontSize'] + 4,
                'fontFamily' => $this->config['labels']['fontFamily'],
                'fill' => $this->config['labels']['color'],
                'fontWeight' => 'bold'
            ]
        );
    }

    /**
     * Render grid lines
     */
    protected function renderGrid(array $xLines = [], array $yLines = []): void
    {
        if (!$this->config['grid']['show']) {
            return;
        }

        $chartArea = $this->getChartArea();
        $gridConfig = [
            'stroke' => $this->config['grid']['color'],
            'strokeWidth' => $this->config['grid']['strokeWidth']
        ];

        // Vertical grid lines
        foreach ($xLines as $x) {
            $this->renderer->addLine(
                $x,
                $chartArea['y'],
                $x,
                $chartArea['y'] + $chartArea['height'],
                $gridConfig
            );
        }

        // Horizontal grid lines
        foreach ($yLines as $y) {
            $this->renderer->addLine(
                $chartArea['x'],
                $y,
                $chartArea['x'] + $chartArea['width'],
                $y,
                $gridConfig
            );
        }
    }

    /**
     * Render chart axes
     */
    protected function renderAxes(): void
    {
        if (!$this->config['axis']['show']) {
            return;
        }

        $chartArea = $this->getChartArea();
        $axisConfig = [
            'stroke' => $this->config['axis']['color'],
            'strokeWidth' => $this->config['axis']['strokeWidth']
        ];

        // X-axis
        $this->renderer->addLine(
            $chartArea['x'],
            $chartArea['y'] + $chartArea['height'],
            $chartArea['x'] + $chartArea['width'],
            $chartArea['y'] + $chartArea['height'],
            $axisConfig
        );

        // Y-axis
        $this->renderer->addLine(
            $chartArea['x'],
            $chartArea['y'],
            $chartArea['x'],
            $chartArea['y'] + $chartArea['height'],
            $axisConfig
        );
    }

    // Abstract methods to be implemented by specific chart types
    abstract protected function validateSpecific(): bool;
    abstract protected function renderChart(): void;
    abstract protected function renderLegend(): void;
    abstract public function getType(): string;

    /**
     * Merge configuration arrays properly without deep recursion issues
     */
    protected function mergeConfig(array $default, array $custom): array
    {
        foreach ($custom as $key => $value) {
            if (is_array($value) && isset($default[$key]) && is_array($default[$key])) {
                $default[$key] = $this->mergeConfig($default[$key], $value);
            } else {
                $default[$key] = $value;
            }
        }
        return $default;
    }
}