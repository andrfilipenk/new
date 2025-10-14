<?php

namespace Core\Calendar\Models;

use DateTimeInterface;
use DateTimeImmutable;
use Core\Calendar\Exceptions\InvalidDateRangeException;

/**
 * Represents a bar (task/event) overlay on the calendar
 */
class Bar
{
    private string $id;
    private string $title;
    private DateTimeImmutable $startDate;
    private DateTimeImmutable $endDate;
    private ?string $color;
    private ?string $backgroundColor;
    private ?string $url;
    private ?string $clickHandler;
    private array $metadata;
    private int $zIndex;

    public function __construct(
        string $id,
        string $title,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        ?string $color = null,
        ?string $backgroundColor = null,
        ?string $url = null,
        ?string $clickHandler = null,
        array $metadata = [],
        int $zIndex = 0
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->startDate = DateTimeImmutable::createFromInterface($startDate);
        $this->endDate = DateTimeImmutable::createFromInterface($endDate);
        
        if ($this->startDate > $this->endDate) {
            throw new InvalidDateRangeException($this->startDate, $this->endDate, 'Bar start date must be before end date');
        }
        
        $this->color = $color;
        $this->backgroundColor = $backgroundColor ?? '#007bff';
        $this->url = $url;
        $this->clickHandler = $clickHandler;
        $this->metadata = $metadata;
        $this->zIndex = $zIndex;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function getBackgroundColor(): ?string
    {
        return $this->backgroundColor;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getClickHandler(): ?string
    {
        return $this->clickHandler;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getZIndex(): int
    {
        return $this->zIndex;
    }

    public function getDurationInDays(): int
    {
        return $this->startDate->diff($this->endDate)->days + 1;
    }

    public function overlaps(Bar $other): bool
    {
        return $this->startDate <= $other->endDate && $this->endDate >= $other->startDate;
    }

    public function spansDate(DateTimeInterface $date): bool
    {
        $checkDate = DateTimeImmutable::createFromInterface($date);
        $checkDate = $checkDate->setTime(0, 0, 0);
        $start = $this->startDate->setTime(0, 0, 0);
        $end = $this->endDate->setTime(0, 0, 0);
        
        return $checkDate >= $start && $checkDate <= $end;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'startDate' => $this->startDate->format('Y-m-d H:i:s'),
            'endDate' => $this->endDate->format('Y-m-d H:i:s'),
            'color' => $this->color,
            'backgroundColor' => $this->backgroundColor,
            'url' => $this->url,
            'clickHandler' => $this->clickHandler,
            'metadata' => $this->metadata,
            'zIndex' => $this->zIndex,
        ];
    }
}
