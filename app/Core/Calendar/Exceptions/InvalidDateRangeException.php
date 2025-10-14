<?php

namespace Core\Calendar\Exceptions;

use DateTimeInterface;

/**
 * Exception thrown when an invalid date range is provided
 */
class InvalidDateRangeException extends CalendarException
{
    private ?DateTimeInterface $startDate;
    private ?DateTimeInterface $endDate;

    public function __construct(?DateTimeInterface $startDate, ?DateTimeInterface $endDate, string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        
        if ($message === '') {
            $message = sprintf(
                'Invalid date range: start date (%s) must be before end date (%s)',
                $startDate?->format('Y-m-d') ?? 'null',
                $endDate?->format('Y-m-d') ?? 'null'
            );
        }
        
        parent::__construct($message, $code, $previous);
    }

    public function getStartDate(): ?DateTimeInterface
    {
        return $this->startDate;
    }

    public function getEndDate(): ?DateTimeInterface
    {
        return $this->endDate;
    }
}
