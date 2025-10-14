<?php

namespace Core\Calendar\Styling\Themes;

use Core\Calendar\Styling\Theme;

/**
 * Default blue-based theme
 */
class DefaultTheme extends Theme
{
    protected string $primaryColor = '#007bff';
    protected string $backgroundColor = '#ffffff';
    protected string $borderColor = '#dddddd';
    protected string $textColor = '#333333';
    protected string $headerBackground = '#f8f9fa';
    protected string $todayColor = '#e3f2fd';
    protected string $selectedColor = '#bbdefb';
    protected string $weekendColor = '#f5f5f5';
    protected int $fontSize = 14;
    protected string $fontFamily = 'system-ui, -apple-system, sans-serif';
    protected string $secondaryTextColor = '#999999';
    protected string $hoverColor = '#f0f0f0';
}
