<?php

namespace Core\Calendar\DataProviders;

use Core\Calendar\Models\DateRange;
use Core\Calendar\Models\CalendarData;
use Core\Calendar\Models\Bar;

/**
 * Callback-based data provider for dynamic data fetching
 */
class CallbackDataProvider implements DataProviderInterface
{
    /** @var callable */
    private $callback;
    private array $metadata = [];

    /**
     * @param callable $callback Function that receives DateRange and returns Bar[]
     * @param array $metadata
     */
    public function __construct(callable $callback, array $metadata = [])
    {
        $this->callback = $callback;
        $this->metadata = $metadata;
    }

    public function getDataForRange(DateRange $range): CalendarData
    {
        $bars = $this->getBarsForRange($range);
        return new CalendarData($bars, $this->metadata);
    }

    public function getBarsForRange(DateRange $range): array
    {
        $result = call_user_func($this->callback, $range);
        
        if (!is_array($result)) {
            return [];
        }
        
        // Filter to ensure all items are Bar instances
        return array_filter($result, fn($item) => $item instanceof Bar);
    }

    public function hasDataForDate(\DateTimeInterface $date): bool
    {
        // Create a single-day range
        $range = new DateRange($date, $date);
        $bars = $this->getBarsForRange($range);
        
        return !empty($bars);
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
