<?php

namespace Core\Calendar\Styling;

/**
 * Interface for calendar themes
 */
interface ThemeInterface
{
    public function getPrimaryColor(): string;
    public function getBackgroundColor(): string;
    public function getBorderColor(): string;
    public function getTextColor(): string;
    public function getHeaderBackground(): string;
    public function getTodayColor(): string;
    public function getSelectedColor(): string;
    public function getWeekendColor(): string;
    public function getFontSize(): int;
    public function getFontFamily(): string;
    public function getSecondaryTextColor(): string;
    public function getHoverColor(): string;
}
