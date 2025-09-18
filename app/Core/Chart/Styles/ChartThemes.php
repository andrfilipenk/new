<?php
// app/Core/Chart/Styles/ChartThemes.php
namespace Core\Chart\Styles;

/**
 * Predefined chart themes and styling configurations
 * Following super-senior PHP practices with configuration patterns
 */
class ChartThemes
{
    public static function modern(): array
    {
        return [
            'colors' => ['#667eea', '#764ba2', '#f093fb', '#f5576c', '#4facfe', '#00f2fe'],
            'background' => '#ffffff',
            'grid' => [
                'show' => true,
                'color' => '#f0f0f0',
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
                'fontFamily' => 'Segoe UI, Arial, sans-serif'
            ],
            'legend' => [
                'show' => true,
                'position' => 'right',
                'color' => '#333333',
                'fontSize' => 11
            ],
            'padding' => [
                'top' => 40,
                'right' => 120,
                'bottom' => 60,
                'left' => 80
            ]
        ];
    }

    public static function dark(): array
    {
        return [
            'colors' => ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57', '#ff9ff3'],
            'background' => '#2c3e50',
            'grid' => [
                'show' => true,
                'color' => '#34495e',
                'strokeWidth' => 1
            ],
            'axis' => [
                'show' => true,
                'color' => '#ecf0f1',
                'strokeWidth' => 2
            ],
            'labels' => [
                'show' => true,
                'color' => '#ecf0f1',
                'fontSize' => 12,
                'fontFamily' => 'Segoe UI, Arial, sans-serif'
            ],
            'legend' => [
                'show' => true,
                'position' => 'right',
                'color' => '#ecf0f1',
                'fontSize' => 11
            ],
            'padding' => [
                'top' => 40,
                'right' => 120,
                'bottom' => 60,
                'left' => 80
            ]
        ];
    }

    public static function minimal(): array
    {
        return [
            'colors' => ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6'],
            'background' => '#ffffff',
            'grid' => [
                'show' => false,
                'color' => '#e0e0e0',
                'strokeWidth' => 1
            ],
            'axis' => [
                'show' => true,
                'color' => '#bdc3c7',
                'strokeWidth' => 1
            ],
            'labels' => [
                'show' => true,
                'color' => '#7f8c8d',
                'fontSize' => 11,
                'fontFamily' => 'Arial, sans-serif'
            ],
            'legend' => [
                'show' => true,
                'position' => 'right',
                'color' => '#7f8c8d',
                'fontSize' => 10
            ],
            'padding' => [
                'top' => 30,
                'right' => 100,
                'bottom' => 50,
                'left' => 70
            ]
        ];
    }

    public static function corporate(): array
    {
        return [
            'colors' => ['#1f4e79', '#2e8b57', '#b8860b', '#8b4513', '#4682b4', '#708090'],
            'background' => '#ffffff',
            'grid' => [
                'show' => true,
                'color' => '#e6e6e6',
                'strokeWidth' => 1
            ],
            'axis' => [
                'show' => true,
                'color' => '#2c3e50',
                'strokeWidth' => 2
            ],
            'labels' => [
                'show' => true,
                'color' => '#2c3e50',
                'fontSize' => 12,
                'fontFamily' => 'Times New Roman, serif'
            ],
            'legend' => [
                'show' => true,
                'position' => 'right',
                'color' => '#2c3e50',
                'fontSize' => 11
            ],
            'padding' => [
                'top' => 50,
                'right' => 130,
                'bottom' => 70,
                'left' => 90
            ]
        ];
    }

    public static function vibrant(): array
    {
        return [
            'colors' => ['#ff4757', '#2ed573', '#1e90ff', '#ffa502', '#ff6348', '#5352ed'],
            'background' => '#ffffff',
            'grid' => [
                'show' => true,
                'color' => '#f1f2f6',
                'strokeWidth' => 1
            ],
            'axis' => [
                'show' => true,
                'color' => '#57606f',
                'strokeWidth' => 2
            ],
            'labels' => [
                'show' => true,
                'color' => '#2f3542',
                'fontSize' => 12,
                'fontFamily' => 'Arial, sans-serif'
            ],
            'legend' => [
                'show' => true,
                'position' => 'right',
                'color' => '#2f3542',
                'fontSize' => 11
            ],
            'padding' => [
                'top' => 40,
                'right' => 120,
                'bottom' => 60,
                'left' => 80
            ]
        ];
    }

    /**
     * Get all available themes
     */
    public static function all(): array
    {
        return [
            'modern' => self::modern(),
            'dark' => self::dark(),
            'minimal' => self::minimal(),
            'corporate' => self::corporate(),
            'vibrant' => self::vibrant()
        ];
    }

    /**
     * Get theme by name
     */
    public static function get(string $theme): array
    {
        $themes = self::all();
        
        if (!isset($themes[$theme])) {
            throw new \InvalidArgumentException("Unknown theme: {$theme}");
        }
        
        return $themes[$theme];
    }
}