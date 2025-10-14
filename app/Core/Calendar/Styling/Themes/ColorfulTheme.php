<?php

namespace Core\Calendar\Styling\Themes;

use Core\Calendar\Styling\Theme;

/**
 * Colorful theme with vibrant colors
 */
class ColorfulTheme extends Theme
{
    protected string $primaryColor = '#ff6b6b';
    protected string $backgroundColor = '#ffffff';
    protected string $borderColor = '#e0e0e0';
    protected string $textColor = '#2c3e50';
    protected string $headerBackground = '#fff3e0';
    protected string $todayColor = '#ffe0b2';
    protected string $selectedColor = '#ffcc80';
    protected string $weekendColor = '#f3e5f5';
    protected int $fontSize = 14;
    protected string $fontFamily = 'system-ui, -apple-system, sans-serif';
    protected string $secondaryTextColor = '#95a5a6';
    protected string $hoverColor = '#fff8e1';
}
