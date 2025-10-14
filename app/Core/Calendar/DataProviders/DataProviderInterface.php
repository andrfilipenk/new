<?php

namespace Core\Calendar\DataProviders;

use Core\Calendar\Models\DateRange;
use Core\Calendar\Models\CalendarData;
use Core\Calendar\Models\Bar;

/**
 * Interface for providing calendar data
 */
interface DataProviderInterface
{
    /**
     * Get calendar data for a date range
     */
    public function getDataForRange(DateRange $range): CalendarData;

    /**
     * Get bars for a date range
     * @return Bar[]
     */
    public function getBarsForRange(DateRange $range): array;

    /**
     * Check if a date has data
     */
    public function hasDataForDate(\DateTimeInterface $date): bool;
}
