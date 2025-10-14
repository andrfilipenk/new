<?php

namespace Core\Calendar\Models;

/**
 * Container for calendar data (events, tasks, bars)
 */
class CalendarData
{
    /** @var Bar[] */
    private array $bars = [];
    
    private array $metadata = [];

    public function __construct(array $bars = [], array $metadata = [])
    {
        foreach ($bars as $bar) {
            $this->addBar($bar);
        }
        $this->metadata = $metadata;
    }

    public function addBar(Bar $bar): void
    {
        $this->bars[$bar->getId()] = $bar;
    }

    public function removeBar(string $id): void
    {
        unset($this->bars[$id]);
    }

    public function getBar(string $id): ?Bar
    {
        return $this->bars[$id] ?? null;
    }

    /**
     * @return Bar[]
     */
    public function getBars(): array
    {
        return array_values($this->bars);
    }

    /**
     * Get bars sorted by start date and z-index
     * @return Bar[]
     */
    public function getSortedBars(): array
    {
        $bars = $this->getBars();
        usort($bars, function (Bar $a, Bar $b) {
            $dateCompare = $a->getStartDate() <=> $b->getStartDate();
            if ($dateCompare !== 0) {
                return $dateCompare;
            }
            return $a->getZIndex() <=> $b->getZIndex();
        });
        return $bars;
    }

    /**
     * Get bars for a specific date
     * @param \DateTimeInterface $date
     * @return Bar[]
     */
    public function getBarsForDate(\DateTimeInterface $date): array
    {
        return array_filter($this->bars, fn(Bar $bar) => $bar->spansDate($date));
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function isEmpty(): bool
    {
        return empty($this->bars);
    }

    public function count(): int
    {
        return count($this->bars);
    }
}
