<?php

namespace Core\Calendar\Styling\Themes;

use Core\Calendar\Styling\Theme;

/**
 * Dark mode theme with high contrast
 */
class DarkTheme extends Theme
{
    protected string $primaryColor = '#1e88e5';
    protected string $backgroundColor = '#1e1e1e';
    protected string $borderColor = '#444444';
    protected string $textColor = '#e0e0e0';
    protected string $headerBackground = '#2d2d2d';
    protected string $todayColor = '#0d47a1';
    protected string $selectedColor = '#1565c0';
    protected string $weekendColor = '#2a2a2a';
    protected int $fontSize = 14;
    protected string $fontFamily = 'system-ui, -apple-system, sans-serif';
    protected string $secondaryTextColor = '#888888';
    protected string $hoverColor = '#333333';
}
