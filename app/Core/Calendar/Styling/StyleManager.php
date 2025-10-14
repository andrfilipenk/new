<?php

namespace Core\Calendar\Styling;

/**
 * Manages styling and theme application
 */
class StyleManager
{
    private ThemeInterface $theme;
    private ColorScheme $colorScheme;

    public function __construct(?ThemeInterface $theme = null, ?ColorScheme $colorScheme = null)
    {
        $this->theme = $theme ?? new Themes\DefaultTheme();
        $this->colorScheme = $colorScheme ?? new ColorScheme();
    }

    public function getTheme(): ThemeInterface
    {
        return $this->theme;
    }

    public function setTheme(ThemeInterface $theme): void
    {
        $this->theme = $theme;
    }

    public function getColorScheme(): ColorScheme
    {
        return $this->colorScheme;
    }

    public function setColorScheme(ColorScheme $colorScheme): void
    {
        $this->colorScheme = $colorScheme;
    }

    /**
     * Generate CSS styles for calendar
     */
    public function generateStyles(string $calendarName): string
    {
        $theme = $this->theme;
        
        return <<<CSS
.calendar-{$calendarName} {
    font-family: {$theme->getFontFamily()};
    font-size: {$theme->getFontSize()}px;
    background-color: {$theme->getBackgroundColor()};
    color: {$theme->getTextColor()};
}
.calendar-{$calendarName} .header {
    background-color: {$theme->getHeaderBackground()};
    border-bottom: 1px solid {$theme->getBorderColor()};
}
.calendar-{$calendarName} .cell {
    border: 1px solid {$theme->getBorderColor()};
}
.calendar-{$calendarName} .cell.weekend {
    background-color: {$theme->getWeekendColor()};
}
.calendar-{$calendarName} .cell.today {
    background-color: {$theme->getTodayColor()};
}
.calendar-{$calendarName} .cell.selected {
    background-color: {$theme->getSelectedColor()};
}
.calendar-{$calendarName} .cell:hover {
    background-color: {$theme->getHoverColor()};
}
.calendar-{$calendarName} .bar {
    cursor: pointer;
    transition: opacity 0.2s;
}
.calendar-{$calendarName} .bar:hover {
    opacity: 0.8;
}
.calendar-{$calendarName} .secondary-text {
    color: {$theme->getSecondaryTextColor()};
}
CSS;
    }

    /**
     * Get style attribute string for an element
     */
    public function getStyleAttribute(array $styles): string
    {
        $parts = [];
        foreach ($styles as $property => $value) {
            $parts[] = "{$property}: {$value}";
        }
        return implode('; ', $parts);
    }
}
