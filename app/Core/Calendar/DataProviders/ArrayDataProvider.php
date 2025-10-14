<?php

namespace Core\Calendar\DataProviders;

use Core\Calendar\Models\DateRange;
use Core\Calendar\Models\CalendarData;
use Core\Calendar\Models\Bar;

/**
 * Simple array-based data provider
 */
class ArrayDataProvider implements DataProviderInterface
{
    /** @var Bar[] */
    private array $bars = [];
    private array $metadata = [];

    /**
     * @param Bar[] $bars
     * @param array $metadata
     */
    public function __construct(array $bars = [], array $metadata = [])
    {
        $this->bars = $bars;
        $this->metadata = $metadata;
    }

    public function addBar(Bar $bar): void
    {
        $this->bars[] = $bar;
    }

    public function getDataForRange(DateRange $range): CalendarData
    {
        $bars = $this->getBarsForRange($range);
        return new CalendarData($bars, $this->metadata);
    }

    public function getBarsForRange(DateRange $range): array
    {
        return array_filter($this->bars, function (Bar $bar) use ($range) {
            // Check if bar overlaps with the range
            return $bar->getStartDate() <= $range->getEndDate() 
                && $bar->getEndDate() >= $range->getStartDate();
        });
    }

    public function hasDataForDate(\DateTimeInterface $date): bool
    {
        foreach ($this->bars as $bar) {
            if ($bar->spansDate($date)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get all bars
     * @return Bar[]
     */
    public function getAllBars(): array
    {
        return $this->bars;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
