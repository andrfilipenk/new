<?php

namespace Core\Calendar\Models;

use DateTimeInterface;
use DateTimeImmutable;

/**
 * Represents a calendar week with week number and year
 */
class Week
{
    private int $weekNumber;
    private int $year;
    private DateTimeImmutable $startDate;
    private DateTimeImmutable $endDate;

    public function __construct(int $weekNumber, int $year)
    {
        $this->weekNumber = $weekNumber;
        $this->year = $year;
        
        // Calculate start and end dates for the week
        $date = new DateTimeImmutable();
        $date = $date->setISODate($year, $weekNumber, 1);
        $this->startDate = $date;
        $this->endDate = $date->modify('+6 days');
    }

    public function getWeekNumber(): int
    {
        return $this->weekNumber;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function contains(DateTimeInterface $date): bool
    {
        $checkDate = DateTimeImmutable::createFromInterface($date);
        return $checkDate >= $this->startDate && $checkDate <= $this->endDate;
    }

    public function toArray(): array
    {
        return [
            'weekNumber' => $this->weekNumber,
            'year' => $this->year,
            'startDate' => $this->startDate->format('Y-m-d'),
            'endDate' => $this->endDate->format('Y-m-d'),
        ];
    }
}
