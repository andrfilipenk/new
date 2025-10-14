<?php

namespace Core\Calendar\Styling;

/**
 * Abstract base theme class
 */
abstract class Theme implements ThemeInterface
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

    public function getPrimaryColor(): string
    {
        return $this->primaryColor;
    }

    public function getBackgroundColor(): string
    {
        return $this->backgroundColor;
    }

    public function getBorderColor(): string
    {
        return $this->borderColor;
    }

    public function getTextColor(): string
    {
        return $this->textColor;
    }

    public function getHeaderBackground(): string
    {
        return $this->headerBackground;
    }

    public function getTodayColor(): string
    {
        return $this->todayColor;
    }

    public function getSelectedColor(): string
    {
        return $this->selectedColor;
    }

    public function getWeekendColor(): string
    {
        return $this->weekendColor;
    }

    public function getFontSize(): int
    {
        return $this->fontSize;
    }

    public function getFontFamily(): string
    {
        return $this->fontFamily;
    }

    public function getSecondaryTextColor(): string
    {
        return $this->secondaryTextColor;
    }

    public function getHoverColor(): string
    {
        return $this->hoverColor;
    }
}
