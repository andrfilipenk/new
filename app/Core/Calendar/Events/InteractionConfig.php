<?php

namespace Core\Calendar\Events;

/**
 * Configuration for calendar interactions
 */
class InteractionConfig
{
    private bool $selectable;
    private bool $rangeSelection;
    private bool $clickable;
    private ?string $onDateClick;
    private ?string $onRangeSelect;
    private ?string $onBarClick;
    private ?string $onWeekClick;
    private ?string $onNavigate;
    private array $formFields;

    public function __construct(
        bool $selectable = true,
        bool $rangeSelection = false,
        bool $clickable = true,
        ?string $onDateClick = null,
        ?string $onRangeSelect = null,
        ?string $onBarClick = null,
        ?string $onWeekClick = null,
        ?string $onNavigate = null,
        array $formFields = []
    ) {
        $this->selectable = $selectable;
        $this->rangeSelection = $rangeSelection;
        $this->clickable = $clickable;
        $this->onDateClick = $onDateClick;
        $this->onRangeSelect = $onRangeSelect;
        $this->onBarClick = $onBarClick;
        $this->onWeekClick = $onWeekClick;
        $this->onNavigate = $onNavigate;
        $this->formFields = $formFields;
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

    public function getOnDateClick(): ?string
    {
        return $this->onDateClick;
    }

    public function getOnRangeSelect(): ?string
    {
        return $this->onRangeSelect;
    }

    public function getOnBarClick(): ?string
    {
        return $this->onBarClick;
    }

    public function getOnWeekClick(): ?string
    {
        return $this->onWeekClick;
    }

    public function getOnNavigate(): ?string
    {
        return $this->onNavigate;
    }

    public function getFormFields(): array
    {
        return $this->formFields;
    }
}
