<?php

namespace Core\Calendar\Utilities;

use Core\Calendar\Models\Bar;
use Core\Calendar\Models\Dimensions;
use DateTimeInterface;
use DateTimeImmutable;

/**
 * Utility class for calculating positions of bars on the calendar
 */
class PositionCalculator
{
    /**
     * Calculate bar position for day/week view (time-based)
     */
    public static function calculateTimeBasedPosition(
        Bar $bar,
        DateTimeInterface $viewStartDate,
        int $dayColumn,
        int $startHour,
        int $endHour,
        int $hourHeight,
        int $columnWidth,
        int $offsetX = 0,
        int $offsetY = 0
    ): array {
        $startTime = $bar->getStartDate();
        $endTime = $bar->getEndDate();
        
        // Calculate hours from start of day
        $startHourFloat = (int)$startTime->format('H') + ((int)$startTime->format('i') / 60);
        $endHourFloat = (int)$endTime->format('H') + ((int)$endTime->format('i') / 60);
        
        // Clamp to view hours
        $startHourFloat = max($startHour, min($endHour, $startHourFloat));
        $endHourFloat = max($startHour, min($endHour, $endHourFloat));
        
        // Calculate Y position based on time
        $y = $offsetY + (($startHourFloat - $startHour) * $hourHeight);
        $height = ($endHourFloat - $startHourFloat) * $hourHeight;
        
        // Calculate X position based on day column
        $x = $offsetX + ($dayColumn * $columnWidth);
        
        return [
            'x' => $x + 2, // Small padding
            'y' => $y,
            'width' => $columnWidth - 4, // Small padding
            'height' => max($height, 20), // Minimum height
        ];
    }

    /**
     * Calculate bar position for month/multi-week view (date-based)
     */
    public static function calculateDateBasedPosition(
        Bar $bar,
        array $dateGrid,
        int $cellWidth,
        int $cellHeight,
        int $barHeight = 20,
        int $barSpacing = 2,
        int $maxBarsPerCell = 3
    ): array {
        $positions = [];
        $startDate = $bar->getStartDate()->setTime(0, 0, 0);
        $endDate = $bar->getEndDate()->setTime(0, 0, 0);
        
        // Find start cell
        $startCell = null;
        $endCell = null;
        
        foreach ($dateGrid as $weekIndex => $week) {
            foreach ($week as $dayIndex => $cellDate) {
                $cellDateNorm = $cellDate->setTime(0, 0, 0);
                if ($cellDateNorm->format('Y-m-d') === $startDate->format('Y-m-d')) {
                    $startCell = ['week' => $weekIndex, 'day' => $dayIndex];
                }
                if ($cellDateNorm->format('Y-m-d') === $endDate->format('Y-m-d')) {
                    $endCell = ['week' => $weekIndex, 'day' => $dayIndex];
                }
            }
        }
        
        if (!$startCell || !$endCell) {
            return [];
        }
        
        // Calculate position for single row or split into multiple rows
        if ($startCell['week'] === $endCell['week']) {
            // Single row bar
            $x = $startCell['day'] * $cellWidth;
            $y = $startCell['week'] * $cellHeight + $barSpacing;
            $width = ($endCell['day'] - $startCell['day'] + 1) * $cellWidth;
            
            $positions[] = [
                'x' => $x + 2,
                'y' => $y,
                'width' => $width - 4,
                'height' => $barHeight,
            ];
        } else {
            // Multi-row bar - split into segments
            for ($week = $startCell['week']; $week <= $endCell['week']; $week++) {
                $dayStart = ($week === $startCell['week']) ? $startCell['day'] : 0;
                $dayEnd = ($week === $endCell['week']) ? $endCell['day'] : 6;
                
                $x = $dayStart * $cellWidth;
                $y = $week * $cellHeight + $barSpacing;
                $width = ($dayEnd - $dayStart + 1) * $cellWidth;
                
                $positions[] = [
                    'x' => $x + 2,
                    'y' => $y,
                    'width' => $width - 4,
                    'height' => $barHeight,
                ];
            }
        }
        
        return $positions;
    }

    /**
     * Stack overlapping bars vertically
     */
    public static function stackBars(array $bars, int $barHeight = 20, int $spacing = 2): array
    {
        $stacked = [];
        
        foreach ($bars as $bar) {
            $level = 0;
            
            // Find the first available level
            while (self::hasOverlapAtLevel($bar, $stacked, $level)) {
                $level++;
            }
            
            $stacked[] = [
                'bar' => $bar,
                'level' => $level,
                'yOffset' => $level * ($barHeight + $spacing),
            ];
        }
        
        return $stacked;
    }

    /**
     * Check if a bar overlaps with any bars at a specific level
     */
    private static function hasOverlapAtLevel(Bar $bar, array $stackedBars, int $level): bool
    {
        foreach ($stackedBars as $stacked) {
            if ($stacked['level'] === $level && $bar->overlaps($stacked['bar'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Calculate grid of dates for a date range
     * @return array Array of weeks, each containing array of DateTimeImmutable
     */
    public static function calculateDateGrid(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        int $firstDayOfWeek = 0
    ): array {
        $grid = [];
        $current = DateTimeImmutable::createFromInterface($startDate)->setTime(0, 0, 0);
        $end = DateTimeImmutable::createFromInterface($endDate)->setTime(0, 0, 0);
        
        // Adjust to start of week
        $dayOfWeek = (int)$current->format('w');
        $diff = ($dayOfWeek - $firstDayOfWeek + 7) % 7;
        $current = $current->modify("-{$diff} days");
        
        $week = [];
        while ($current <= $end || count($week) > 0) {
            $week[] = $current;
            
            if (count($week) === 7) {
                $grid[] = $week;
                $week = [];
            }
            
            $current = $current->modify('+1 day');
            
            if ($current > $end && count($week) === 0) {
                break;
            }
        }
        
        return $grid;
    }
}
