<?php
// app/Core/Chart/Styles/CssGenerator.php
namespace Core\Chart\Styles;

/**
 * CSS Generator for chart styling and animations
 * Generates CSS for interactive and responsive charts
 */
class CssGenerator
{
    /**
     * Generate basic chart CSS
     */
    public static function basic(): string
    {
        return '
        /* Basic Chart Styles */
        .chart-container {
            position: relative;
            display: inline-block;
        }
        .chart-svg {
            max-width: 100%;
            height: auto;
        }
        /* Interactive elements */
        .bar:hover,
        .slice:hover,
        .point:hover {
            opacity: 0.8;
            cursor: pointer;
        }
        /* Tooltips */
        .chart-tooltip {
            position: absolute;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            pointer-events: none;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .chart-tooltip.show {
            opacity: 1;
        }';
    }

    /**
     * Generate animation CSS
     */
    public static function animations(): string
    {
        return '
        /* Chart Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        @keyframes grow {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }
        @keyframes drawLine {
            from { stroke-dashoffset: 1000; }
            to { stroke-dashoffset: 0; }
        }
        /* Apply animations */
        .chart-svg {
            animation: fadeIn 0.5s ease-in-out;
        }
        .bar {
            animation: slideUp 0.6s ease-out;
        }
        .slice {
            animation: grow 0.5s ease-out;
            transform-origin: center;
        }
        .line {
            stroke-dasharray: 1000;
            animation: drawLine 1.5s ease-out forwards;
        }
        .point {
            animation: grow 0.3s ease-out;
            transform-origin: center;
        }';
    }

    /**
     * Generate responsive CSS
     */
    public static function responsive(): string
    {
        return '
        /* Responsive Chart Styles */
        .chart-container {
            width: 100%;
            max-width: 100%;
        }
        @media (max-width: 768px) {
            .chart-svg {
                width: 100%;
                height: auto;
            }
            /* Adjust text sizes for mobile */
            text {
                font-size: 10px !important;
            }
            /* Hide legend on small screens */
            .legend {
                display: none;
            }
        }
        @media (max-width: 480px) {
            text {
                font-size: 8px !important;
            }
            /* Smaller padding for mobile */
            .chart-container {
                padding: 10px;
            }
        }';
    }

    /**
     * Generate dark theme CSS
     */
    public static function darkTheme(): string
    {
        return '
        /* Dark Theme Styles */
        .chart-dark {
            background-color: #2c3e50;
            color: #ecf0f1;
        }
        .chart-dark .grid-line {
            stroke: #34495e;
        }
        .chart-dark .axis-line {
            stroke: #ecf0f1;
        }
        .chart-dark text {
            fill: #ecf0f1;
        }
        .chart-dark .chart-tooltip {
            background: rgba(52, 73, 94, 0.9);
            border: 1px solid #7f8c8d;
        }';
    }

    /**
     * Generate complete CSS with all styles
     */
    public static function complete(): string
    {
        return self::basic() . "\n" . 
               self::animations() . "\n" . 
               self::responsive() . "\n" . 
               self::darkTheme();
    }

    /**
     * Generate CSS for specific chart type
     */
    public static function forType(string $type): string
    {
        $base = self::basic();
        switch ($type) {
            case 'bar':
                return $base . '
                /* Bar Chart Specific */
                .bar {
                    transition: opacity 0.3s, fill 0.3s;
                }
                .bar:hover {
                    fill: #ff6b6b !important;
                }';
            case 'line':
                return $base . '
                /* Line Chart Specific */
                .line {
                    fill: none;
                    stroke-width: 2;
                    transition: stroke-width 0.3s;
                }
                .line:hover {
                    stroke-width: 4;
                }
                .point {
                    transition: r 0.3s;
                }
                .point:hover {
                    r: 6;
                }';
            case 'pie':
                return $base . '
                /* Pie Chart Specific */
                .slice {
                    transition: transform 0.3s;
                }
                .slice:hover {
                    transform: scale(1.05);
                    transform-origin: center;
                }';
            default:
                return $base;
        }
    }
}