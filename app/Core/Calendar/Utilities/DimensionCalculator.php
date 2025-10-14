<?php

namespace Core\Calendar\Utilities;

use Core\Calendar\Models\Dimensions;

/**
 * Utility class for calculating dimensions of calendar elements
 */
class DimensionCalculator
{
    /**
     * Calculate dimensions for day view
     */
    public static function calculateDayViewDimensions(
        int $startHour,
        int $endHour,
        int $hourHeight = 60,
        int $headerHeight = 60,
        int $timeAxisWidth = 60
    ): Dimensions {
        $hours = $endHour - $startHour;
        $bodyHeight = $hours * $hourHeight;
        $totalHeight = $bodyHeight + $headerHeight + 20; // 20 for margins
        $totalWidth = $timeAxisWidth + 400 + 20; // 400 for content, 20 for margins
        
        return new Dimensions(
            width: $totalWidth,
            height: $totalHeight,
            headerHeight: $headerHeight,
            cellWidth: 400,
            cellHeight: $hourHeight,
            marginTop: 10,
            marginRight: 10,
            marginBottom: 10,
            marginLeft: 10
        );
    }

    /**
     * Calculate dimensions for week view
     */
    public static function calculateWeekViewDimensions(
        int $startHour,
        int $endHour,
        int $hourHeight = 40,
        int $headerHeight = 60,
        int $timeAxisWidth = 60,
        int $dayColumnWidth = 120
    ): Dimensions {
        $hours = $endHour - $startHour;
        $bodyHeight = $hours * $hourHeight;
        $totalHeight = $bodyHeight + $headerHeight + 20;
        $totalWidth = $timeAxisWidth + ($dayColumnWidth * 7) + 20;
        
        return new Dimensions(
            width: $totalWidth,
            height: $totalHeight,
            headerHeight: $headerHeight,
            cellWidth: $dayColumnWidth,
            cellHeight: $hourHeight,
            marginTop: 10,
            marginRight: 10,
            marginBottom: 10,
            marginLeft: 10
        );
    }

    /**
     * Calculate dimensions for multi-week view
     */
    public static function calculateMultiWeekDimensions(
        int $weekCount,
        int $cellHeight = 80,
        int $headerHeight = 60,
        int $weekNumberWidth = 40,
        int $dayColumnWidth = 120
    ): Dimensions {
        $bodyHeight = $weekCount * $cellHeight;
        $totalHeight = $bodyHeight + $headerHeight + 20;
        $totalWidth = $weekNumberWidth + ($dayColumnWidth * 7) + 20;
        
        return new Dimensions(
            width: $totalWidth,
            height: $totalHeight,
            headerHeight: $headerHeight,
            cellWidth: $dayColumnWidth,
            cellHeight: $cellHeight,
            marginTop: 10,
            marginRight: 10,
            marginBottom: 10,
            marginLeft: 10
        );
    }

    /**
     * Calculate dimensions for month view
     */
    public static function calculateMonthViewDimensions(
        int $weekCount,
        int $cellHeight = 100,
        int $headerHeight = 80,
        int $weekNumberWidth = 40,
        int $dayColumnWidth = 120,
        bool $showWeekNumbers = false
    ): Dimensions {
        $bodyHeight = $weekCount * $cellHeight;
        $totalHeight = $bodyHeight + $headerHeight + 20;
        $weekNumWidth = $showWeekNumbers ? $weekNumberWidth : 0;
        $totalWidth = $weekNumWidth + ($dayColumnWidth * 7) + 20;
        
        return new Dimensions(
            width: $totalWidth,
            height: $totalHeight,
            headerHeight: $headerHeight,
            cellWidth: $dayColumnWidth,
            cellHeight: $cellHeight,
            marginTop: 10,
            marginRight: 10,
            marginBottom: 10,
            marginLeft: 10
        );
    }

    /**
     * Calculate dimensions for year view
     */
    public static function calculateYearViewDimensions(
        int $columns = 4,
        int $monthWidth = 250,
        int $monthHeight = 200,
        int $headerHeight = 60
    ): Dimensions {
        $rows = (int)ceil(12 / $columns);
        $totalWidth = ($monthWidth * $columns) + 40;
        $totalHeight = ($monthHeight * $rows) + $headerHeight + 40;
        
        return new Dimensions(
            width: $totalWidth,
            height: $totalHeight,
            headerHeight: $headerHeight,
            cellWidth: $monthWidth,
            cellHeight: $monthHeight,
            marginTop: 20,
            marginRight: 20,
            marginBottom: 20,
            marginLeft: 20
        );
    }

    /**
     * Calculate cell position in grid
     */
    public static function calculateCellPosition(
        int $row,
        int $column,
        int $cellWidth,
        int $cellHeight,
        int $offsetX = 0,
        int $offsetY = 0
    ): array {
        return [
            'x' => $offsetX + ($column * $cellWidth),
            'y' => $offsetY + ($row * $cellHeight),
            'width' => $cellWidth,
            'height' => $cellHeight,
        ];
    }
}
