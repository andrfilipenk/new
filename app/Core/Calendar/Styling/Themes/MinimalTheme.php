<?php

namespace Core\Calendar\Styling\Themes;

use Core\Calendar\Styling\Theme;

/**
 * Minimal theme with subtle colors and borders
 */
class MinimalTheme extends Theme
{
    protected string $primaryColor = '#666666';
    protected string $backgroundColor = '#ffffff';
    protected string $borderColor = '#eeeeee';
    protected string $textColor = '#555555';
    protected string $headerBackground = '#fafafa';
    protected string $todayColor = '#f5f5f5';
    protected string $selectedColor = '#e8e8e8';
    protected string $weekendColor = '#fbfbfb';
    protected int $fontSize = 13;
    protected string $fontFamily = 'system-ui, -apple-system, sans-serif';
    protected string $secondaryTextColor = '#aaaaaa';
    protected string $hoverColor = '#f8f8f8';
}
