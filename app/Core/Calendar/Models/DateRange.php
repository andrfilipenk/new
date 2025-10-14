<?php

namespace Core\Calendar\Models;

use DateTimeInterface;
use DateTimeImmutable;
use Core\Calendar\Exceptions\InvalidDateRangeException;

/**
 * Represents a date range for calendar views
 */
class DateRange
{
    private DateTimeImmutable $startDate;
    private DateTimeImmutable $endDate;

    public function __construct(DateTimeInterface $startDate, DateTimeInterface $endDate)
    {
        $this->startDate = DateTimeImmutable::createFromInterface($startDate);
        $this->endDate = DateTimeImmutable::createFromInterface($endDate);
        
        if ($this->startDate > $this->endDate) {
            throw new InvalidDateRangeException($this->startDate, $this->endDate);
        }
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getDurationInDays(): int
    {
        return $this->startDate->diff($this->endDate)->days;
    }

    public function contains(DateTimeInterface $date): bool
    {
        $checkDate = DateTimeImmutable::createFromInterface($date);
        return $checkDate >= $this->startDate && $checkDate <= $this->endDate;
    }

    public function overlaps(DateRange $other): bool
    {
        return $this->startDate <= $other->endDate && $this->endDate >= $other->startDate;
    }

    public function toArray(): array
    {
        return [
            'start' => $this->startDate->format('Y-m-d'),
            'end' => $this->endDate->format('Y-m-d'),
            'duration' => $this->getDurationInDays(),
        ];
    }
}
