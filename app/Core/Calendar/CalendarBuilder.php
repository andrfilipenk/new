<?php

namespace Core\Calendar;

use DateTimeInterface;
use DateTimeImmutable;
use Core\Calendar\Models\Bar;
use Core\Calendar\Models\DateRange;
use Core\Calendar\Styling\ThemeInterface;
use Core\Calendar\DataProviders\DataProviderInterface;
use Core\Calendar\DataProviders\ArrayDataProvider;
use Core\Calendar\Events\InteractionConfig;
use Core\Calendar\Exceptions\InvalidConfigException;
use Core\Calendar\Utilities\DateCalculator;

/**
 * Fluent builder for configuring calendar instances
 */
class CalendarBuilder
{
    private string $name;
    private string $viewType = 'month';
    private ?DateTimeInterface $startDate = null;
    private ?DateTimeInterface $endDate = null;
    private string $locale = 'en_US';
    private int $firstDayOfWeek = 0;
    private bool $weekNumbers = false;
    private bool $selectable = true;
    private bool $rangeSelection = false;
    private bool $clickable = true;
    private string $renderFormat = 'svg';
    private ?ThemeInterface $theme = null;
    private ?DataProviderInterface $dataProvider = null;
    private array $bars = [];
    private ?string $onDateClick = null;
    private ?string $onRangeSelect = null;
    private ?string $onBarClick = null;
    private ?string $onWeekClick = null;
    private ?string $onNavigate = null;
    private array $formFields = [];
    private array $options = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function setView(string $type): self
    {
        $validTypes = ['day', 'week', 'multi-week', 'month', 'year', 'custom'];
        if (!in_array($type, $validTypes)) {
            throw new InvalidConfigException('viewType', $type, "Invalid view type. Must be one of: " . implode(', ', $validTypes));
        }
        $this->viewType = $type;
        return $this;
    }

    public function setDateRange(DateTimeInterface $start, DateTimeInterface $end): self
    {
        $this->startDate = $start;
        $this->endDate = $end;
        return $this;
    }

    public function setDate(DateTimeInterface $date): self
    {
        $this->startDate = $date;
        $this->endDate = null; // Will be calculated based on view type
        return $this;
    }

    public function withLocale(string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    public function setFirstDayOfWeek(int $day): self
    {
        if ($day < 0 || $day > 6) {
            throw new InvalidConfigException('firstDayOfWeek', $day, 'First day of week must be between 0 (Sunday) and 6 (Saturday)');
        }
        $this->firstDayOfWeek = $day;
        return $this;
    }

    public function enableWeekNumbers(bool $enable = true): self
    {
        $this->weekNumbers = $enable;
        return $this;
    }

    public function enableSelection(bool $enable = true): self
    {
        $this->selectable = $enable;
        return $this;
    }

    public function enableRangeSelection(bool $enable = true): self
    {
        $this->rangeSelection = $enable;
        if ($enable) {
            $this->selectable = true;
        }
        return $this;
    }

    public function enableClickable(bool $enable = true): self
    {
        $this->clickable = $enable;
        return $this;
    }

    public function withTheme(ThemeInterface $theme): self
    {
        $this->theme = $theme;
        return $this;
    }

    public function withDataProvider(DataProviderInterface $provider): self
    {
        $this->dataProvider = $provider;
        return $this;
    }

    public function addBar(Bar $bar): self
    {
        $this->bars[] = $bar;
        return $this;
    }

    public function addBars(array $bars): self
    {
        foreach ($bars as $bar) {
            if ($bar instanceof Bar) {
                $this->addBar($bar);
            }
        }
        return $this;
    }

    public function onDateClick(string $handler): self
    {
        $this->onDateClick = $handler;
        return $this;
    }

    public function onRangeSelect(string $handler): self
    {
        $this->onRangeSelect = $handler;
        return $this;
    }

    public function onBarClick(string $handler): self
    {
        $this->onBarClick = $handler;
        return $this;
    }

    public function onWeekClick(string $handler): self
    {
        $this->onWeekClick = $handler;
        return $this;
    }

    public function onNavigate(string $handler): self
    {
        $this->onNavigate = $handler;
        return $this;
    }

    public function bindToFormFields(array $fields): self
    {
        $this->formFields = $fields;
        return $this;
    }

    public function setOption(string $key, mixed $value): self
    {
        $this->options[$key] = $value;
        return $this;
    }

    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    public function build(): Calendar
    {
        // Calculate date range if not set
        if ($this->startDate === null) {
            $this->startDate = new DateTimeImmutable();
        }
        
        if ($this->endDate === null) {
            $this->endDate = $this->calculateEndDate();
        }
        
        // Create data provider if not set
        if ($this->dataProvider === null && !empty($this->bars)) {
            $this->dataProvider = new ArrayDataProvider($this->bars);
        }
        
        // Create interaction config
        $interactionConfig = new InteractionConfig(
            $this->selectable,
            $this->rangeSelection,
            $this->clickable,
            $this->onDateClick,
            $this->onRangeSelect,
            $this->onBarClick,
            $this->onWeekClick,
            $this->onNavigate,
            $this->formFields
        );
        
        // Create config
        $config = new CalendarConfig(
            $this->name,
            $this->viewType,
            $this->startDate,
            $this->endDate,
            $this->locale,
            $this->firstDayOfWeek,
            $this->weekNumbers,
            $this->selectable,
            $this->rangeSelection,
            $this->clickable,
            $this->renderFormat,
            $this->theme,
            $this->dataProvider,
            $interactionConfig,
            $this->options
        );
        
        return new Calendar($config);
    }

    private function calculateEndDate(): DateTimeInterface
    {
        $start = DateTimeImmutable::createFromInterface($this->startDate);
        
        return match ($this->viewType) {
            'day' => $start,
            'week' => DateCalculator::getLastDayOfWeek($start, $this->firstDayOfWeek),
            'multi-week' => $start->modify('+' . ($this->options['weekCount'] ?? 3) . ' weeks'),
            'month' => DateCalculator::getLastDayOfMonth($start),
            'year' => $start->modify('last day of December ' . $start->format('Y')),
            default => $start,
        };
    }
}
