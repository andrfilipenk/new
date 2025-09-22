<?php
// app/Core/Chart/ChartInterface.php
namespace Core\Chart;

/**
 * Chart interface following super-senior PHP practices
 * Defines contract for all chart implementations
 */
interface ChartInterface
{
    /**
     * Set chart data
     */
    public function setData(array $data): self;

    /**
     * Set chart configuration
     */
    public function setConfig(array $config): self;

    /**
     * Set chart dimensions
     */
    public function setDimensions(int $width, int $height): self;

    /**
     * Set chart title
     */
    public function setTitle(string $title): self;

    /**
     * Render chart as SVG string
     */
    public function render(): string;

    /**
     * Get chart type
     */
    public function getType(): string;

    /**
     * Validate chart data and configuration
     */
    public function validate(): bool;

    /**
     * Get chart configuration
     */
    public function getConfig(): array;
}