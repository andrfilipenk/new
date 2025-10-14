<?php

namespace Core\Calendar\Styling;

/**
 * Manages color schemes for calendar elements
 */
class ColorScheme
{
    private array $colors;

    public function __construct(array $colors = [])
    {
        $this->colors = array_merge($this->getDefaultColors(), $colors);
    }

    private function getDefaultColors(): array
    {
        return [
            'primary' => '#007bff',
            'secondary' => '#6c757d',
            'success' => '#28a745',
            'danger' => '#dc3545',
            'warning' => '#ffc107',
            'info' => '#17a2b8',
            'light' => '#f8f9fa',
            'dark' => '#343a40',
        ];
    }

    public function getColor(string $name): ?string
    {
        return $this->colors[$name] ?? null;
    }

    public function setColor(string $name, string $color): void
    {
        $this->colors[$name] = $color;
    }

    public function getAllColors(): array
    {
        return $this->colors;
    }
}
