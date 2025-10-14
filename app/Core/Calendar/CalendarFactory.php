<?php

namespace Core\Calendar;

use DateTimeInterface;
use DateTimeImmutable;
use Core\Calendar\Utilities\DateCalculator;

/**
 * Factory for creating calendar instances with static convenience methods
 */
class CalendarFactory
{
    /**
     * Create a new calendar builder
     */
    public static function create(string $name, array $config = []): CalendarBuilder
    {
        $builder = new CalendarBuilder($name);
        
        // Apply config array if provided
        if (!empty($config)) {
            self::applyConfig($builder, $config);
        }
        
        return $builder;
    }

    /**
     * Create a day view calendar
     */
    public static function day(?DateTimeInterface $date = null, array $config = []): CalendarBuilder
    {
        $date = $date ?? new DateTimeImmutable();
        $name = $config['name'] ?? 'calendar_day_' . $date->format('Ymd');
        
        return self::create($name, $config)
            ->setView('day')
            ->setDate($date);
    }

    /**
     * Create a week view calendar
     */
    public static function week(?DateTimeInterface $date = null, array $config = []): CalendarBuilder
    {
        $date = $date ?? new DateTimeImmutable();
        $name = $config['name'] ?? 'calendar_week_' . $date->format('YW');
        
        return self::create($name, $config)
            ->setView('week')
            ->setDate($date);
    }

    /**
     * Create a multi-week view calendar
     */
    public static function multiWeek(?DateTimeInterface $date = null, int $weekCount = 3, array $config = []): CalendarBuilder
    {
        $date = $date ?? new DateTimeImmutable();
        $name = $config['name'] ?? 'calendar_multiweek_' . $date->format('Ymd');
        
        return self::create($name, $config)
            ->setView('multi-week')
            ->setDate($date)
            ->setOption('weekCount', $weekCount);
    }

    /**
     * Create a month view calendar
     */
    public static function month(?DateTimeInterface $date = null, array $config = []): CalendarBuilder
    {
        $date = $date ?? new DateTimeImmutable();
        $name = $config['name'] ?? 'calendar_month_' . $date->format('Ym');
        
        return self::create($name, $config)
            ->setView('month')
            ->setDate($date);
    }

    /**
     * Create a year view calendar
     */
    public static function year(?int $year = null, array $config = []): CalendarBuilder
    {
        $year = $year ?? (int)date('Y');
        $date = new DateTimeImmutable("{$year}-01-01");
        $name = $config['name'] ?? 'calendar_year_' . $year;
        
        return self::create($name, $config)
            ->setView('year')
            ->setDate($date);
    }

    /**
     * Create a custom period view
     */
    public static function custom(
        DateTimeInterface $start,
        DateTimeInterface $end,
        array $config = []
    ): CalendarBuilder {
        $name = $config['name'] ?? 'calendar_custom_' . $start->format('Ymd') . '_' . $end->format('Ymd');
        
        $builder = self::create($name, $config)
            ->setDateRange($start, $end);
        
        // Auto-detect view type if not specified
        if (!isset($config['viewType'])) {
            $viewType = self::detectViewType($start, $end);
            $builder->setView($viewType);
        }
        
        return $builder;
    }

    /**
     * Auto-detect appropriate view type based on date range
     */
    private static function detectViewType(DateTimeInterface $start, DateTimeInterface $end): string
    {
        $startDate = DateTimeImmutable::createFromInterface($start);
        $endDate = DateTimeImmutable::createFromInterface($end);
        $days = $startDate->diff($endDate)->days;
        
        return match (true) {
            $days === 0 => 'day',
            $days <= 7 => 'week',
            $days <= 35 => 'multi-week',
            $days <= 365 => 'month',
            default => 'year',
        };
    }

    /**
     * Apply configuration array to builder
     */
    private static function applyConfig(CalendarBuilder $builder, array $config): void
    {
        if (isset($config['locale'])) {
            $builder->withLocale($config['locale']);
        }
        
        if (isset($config['firstDayOfWeek'])) {
            $builder->setFirstDayOfWeek($config['firstDayOfWeek']);
        }
        
        if (isset($config['weekNumbers'])) {
            $builder->enableWeekNumbers($config['weekNumbers']);
        }
        
        if (isset($config['selectable'])) {
            $builder->enableSelection($config['selectable']);
        }
        
        if (isset($config['rangeSelection'])) {
            $builder->enableRangeSelection($config['rangeSelection']);
        }
        
        if (isset($config['clickable'])) {
            $builder->enableClickable($config['clickable']);
        }
        
        if (isset($config['theme'])) {
            $builder->withTheme($config['theme']);
        }
        
        if (isset($config['dataProvider'])) {
            $builder->withDataProvider($config['dataProvider']);
        }
        
        if (isset($config['onDateClick'])) {
            $builder->onDateClick($config['onDateClick']);
        }
        
        if (isset($config['onRangeSelect'])) {
            $builder->onRangeSelect($config['onRangeSelect']);
        }
        
        if (isset($config['onBarClick'])) {
            $builder->onBarClick($config['onBarClick']);
        }
        
        if (isset($config['formFields'])) {
            $builder->bindToFormFields($config['formFields']);
        }
        
        if (isset($config['options'])) {
            $builder->setOptions($config['options']);
        }
    }
}
