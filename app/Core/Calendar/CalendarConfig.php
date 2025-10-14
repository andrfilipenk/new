<?php

namespace Core\Calendar;

use DateTimeInterface;
use Core\Calendar\Styling\ThemeInterface;
use Core\Calendar\DataProviders\DataProviderInterface;
use Core\Calendar\Events\InteractionConfig;

/**
 * Immutable configuration value object for calendar
 */
class CalendarConfig
{
    private string $name;
    private string $viewType;
    private DateTimeInterface $startDate;
    private DateTimeInterface $endDate;
    private string $locale;
    private int $firstDayOfWeek;
    private bool $weekNumbers;
    private bool $selectable;
    private bool $rangeSelection;
    private bool $clickable;
    private string $renderFormat;
    private ?ThemeInterface $theme;
    private ?DataProviderInterface $dataProvider;
    private ?InteractionConfig $interactionConfig;
    private array $options;

    public function __construct(
        string $name,
        string $viewType,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        string $locale = 'en_US',
        int $firstDayOfWeek = 0,
        bool $weekNumbers = false,
        bool $selectable = true,
        bool $rangeSelection = false,
        bool $clickable = true,
        string $renderFormat = 'svg',
        ?ThemeInterface $theme = null,
        ?DataProviderInterface $dataProvider = null,
        ?InteractionConfig $interactionConfig = null,
        array $options = []
    ) {
        $this->name = $name;
        $this->viewType = $viewType;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->locale = $locale;
        $this->firstDayOfWeek = $firstDayOfWeek;
        $this->weekNumbers = $weekNumbers;
        $this->selectable = $selectable;
        $this->rangeSelection = $rangeSelection;
        $this->clickable = $clickable;
        $this->renderFormat = $renderFormat;
        $this->theme = $theme;
        $this->dataProvider = $dataProvider;
        $this->interactionConfig = $interactionConfig;
        $this->options = $options;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getViewType(): string
    {
        return $this->viewType;
    }

    public function getStartDate(): DateTimeInterface
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeInterface
    {
        return $this->endDate;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getFirstDayOfWeek(): int
    {
        return $this->firstDayOfWeek;
    }

    public function showWeekNumbers(): bool
    {
        return $this->weekNumbers;
    }

    public function isSelectable(): bool
    {
        return $this->selectable;
    }

    public function isRangeSelection(): bool
    {
        return $this->rangeSelection;
    }

    public function isClickable(): bool
    {
        return $this->clickable;
    }

    public function getRenderFormat(): string
    {
        return $this->renderFormat;
    }

    public function getTheme(): ?ThemeInterface
    {
        return $this->theme;
    }

    public function getDataProvider(): ?DataProviderInterface
    {
        return $this->dataProvider;
    }

    public function getInteractionConfig(): ?InteractionConfig
    {
        return $this->interactionConfig;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }
}
