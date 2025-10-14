<?php

namespace Core\Calendar\Renderers;

use Core\Calendar\CalendarConfig;
use Core\Calendar\Models\CalendarData;
use Core\Calendar\Models\Dimensions;
use Core\Calendar\Svg\SvgBuilder;
use Core\Calendar\Svg\SvgElement;
use Core\Calendar\Utilities\DateCalculator;
use Core\Calendar\Utilities\DimensionCalculator;
use Core\Calendar\Utilities\PositionCalculator;
use DateTimeImmutable;

/**
 * Renderer for multi-week linear view
 */
class MultiWeekRenderer extends AbstractViewRenderer
{
    public function calculateDimensions(CalendarConfig $config): Dimensions
    {
        $weekCount = $config->getOption('weekCount', 3);
        $cellHeight = $config->getOption('cellHeight', 80);
        $headerHeight = $config->getOption('headerHeight', 60);
        $weekNumberWidth = $config->getOption('weekNumberWidth', 40);
        $dayColumnWidth = $config->getOption('dayColumnWidth', 120);
        
        return DimensionCalculator::calculateMultiWeekDimensions(
            $weekCount,
            $cellHeight,
            $headerHeight,
            $weekNumberWidth,
            $dayColumnWidth
        );
    }

    protected function renderHeader(CalendarConfig $config, Dimensions $dimensions): ?SvgElement
    {
        $theme = $this->getTheme($config);
        $headerGroup = SvgBuilder::group(['class' => 'header']);
        
        // Header background
        $headerBg = $this->createThemedRect(
            0,
            0,
            $dimensions->getWidth(),
            $dimensions->getHeaderHeight(),
            $config,
            ['fill' => $theme->getHeaderBackground()]
        );
        $headerGroup->appendChild($headerBg);
        
        // Title
        $startDate = DateTimeImmutable::createFromInterface($config->getStartDate());
        $endDate = DateTimeImmutable::createFromInterface($config->getEndDate());
        $weekCount = $config->getOption('weekCount', 3);
        $title = "{$weekCount} Weeks - " . $startDate->format('M j') . ' to ' . $endDate->format('M j, Y');
        
        $titleText = $this->createThemedText(
            $title,
            $dimensions->getWidth() / 2,
            25,
            $config,
            [
                'text-anchor' => 'middle',
                'font-size' => 16,
                'font-weight' => 'bold',
            ]
        );
        $headerGroup->appendChild($titleText);
        
        // Day headers
        $weekNumWidth = $config->getOption('weekNumberWidth', 40);
        $dayColumnWidth = $dimensions->getCellWidth();
        $offsetX = $weekNumWidth + 10;
        
        for ($i = 0; $i < 7; $i++) {
            $dayNum = ($config->getFirstDayOfWeek() + $i) % 7;
            $dayName = DateCalculator::getDayName($dayNum, $config->getLocale(), true);
            
            $x = $offsetX + ($i * $dayColumnWidth) + ($dayColumnWidth / 2);
            
            $dayText = $this->createThemedText(
                $dayName,
                $x,
                50,
                $config,
                [
                    'text-anchor' => 'middle',
                    'font-size' => 12,
                    'font-weight' => 'bold',
                ]
            );
            $headerGroup->appendChild($dayText);
        }
        
        return $headerGroup;
    }

    protected function renderBody(CalendarConfig $config, CalendarData $data, Dimensions $dimensions): ?SvgElement
    {
        $bodyGroup = SvgBuilder::group(['class' => 'body']);
        $theme = $this->getTheme($config);
        
        $weekCount = $config->getOption('weekCount', 3);
        $weekNumWidth = $config->getOption('weekNumberWidth', 40);
        $dayColumnWidth = $dimensions->getCellWidth();
        $cellHeight = $dimensions->getCellHeight();
        $offsetY = $dimensions->getHeaderHeight();
        $offsetX = $weekNumWidth + 10;
        
        $startDate = DateTimeImmutable::createFromInterface($config->getStartDate());
        $currentDate = DateCalculator::getFirstDayOfWeek($startDate, $config->getFirstDayOfWeek());
        $today = new DateTimeImmutable();
        
        for ($weekIndex = 0; $weekIndex < $weekCount; $weekIndex++) {
            $y = $offsetY + ($weekIndex * $cellHeight);
            
            // Week number
            $weekNum = DateCalculator::getWeekNumber($currentDate);
            $year = DateCalculator::getWeekYear($currentDate);
            
            $weekNumText = $this->createThemedText(
                "W{$weekNum}",
                $weekNumWidth / 2,
                $y + ($cellHeight / 2),
                $config,
                [
                    'text-anchor' => 'middle',
                    'dominant-baseline' => 'middle',
                    'font-size' => 12,
                    'font-weight' => 'bold',
                    'fill' => $theme->getSecondaryTextColor(),
                ]
            );
            $bodyGroup->appendChild($weekNumText);
            
            // Day cells
            for ($dayIndex = 0; $dayIndex < 7; $dayIndex++) {
                $x = $offsetX + ($dayIndex * $dayColumnWidth);
                
                $isWeekend = DateCalculator::isWeekend($currentDate);
                $isToday = DateCalculator::isToday($currentDate);
                
                // Cell background
                $cellAttrs = ['class' => 'cell'];
                if ($isWeekend) {
                    $cellAttrs['fill'] = $theme->getWeekendColor();
                }
                if ($isToday) {
                    $cellAttrs['fill'] = $theme->getTodayColor();
                }
                
                $cellRect = $this->createThemedRect(
                    $x,
                    $y,
                    $dayColumnWidth,
                    $cellHeight,
                    $config,
                    $cellAttrs
                );
                $bodyGroup->appendChild($cellRect);
                
                // Day number
                $dayNum = $currentDate->format('j');
                $dayText = $this->createThemedText(
                    $dayNum,
                    $x + 10,
                    $y + 20,
                    $config,
                    [
                        'font-size' => 14,
                        'font-weight' => $isToday ? 'bold' : 'normal',
                    ]
                );
                $bodyGroup->appendChild($dayText);
                
                $currentDate = $currentDate->modify('+1 day');
            }
        }
        
        return $bodyGroup;
    }

    protected function renderBars(CalendarConfig $config, CalendarData $data, Dimensions $dimensions): ?SvgElement
    {
        if ($data->isEmpty()) {
            return null;
        }
        
        $barsGroup = SvgBuilder::group(['class' => 'bars']);
        
        $weekCount = $config->getOption('weekCount', 3);
        $weekNumWidth = $config->getOption('weekNumberWidth', 40);
        $dayColumnWidth = $dimensions->getCellWidth();
        $cellHeight = $dimensions->getCellHeight();
        $offsetY = $dimensions->getHeaderHeight();
        $offsetX = $weekNumWidth + 10;
        
        // Build date grid
        $startDate = DateTimeImmutable::createFromInterface($config->getStartDate());
        $currentDate = DateCalculator::getFirstDayOfWeek($startDate, $config->getFirstDayOfWeek());
        $dateGrid = [];
        
        for ($weekIndex = 0; $weekIndex < $weekCount; $weekIndex++) {
            $week = [];
            for ($dayIndex = 0; $dayIndex < 7; $dayIndex++) {
                $week[] = $currentDate;
                $currentDate = $currentDate->modify('+1 day');
            }
            $dateGrid[] = $week;
        }
        
        $barHeight = $config->getOption('barHeight', 20);
        $barSpacing = $config->getOption('barSpacing', 2);
        
        foreach ($data->getSortedBars() as $bar) {
            $positions = PositionCalculator::calculateDateBasedPosition(
                $bar,
                $dateGrid,
                $dayColumnWidth,
                $cellHeight,
                $barHeight,
                $barSpacing
            );
            
            foreach ($positions as $pos) {
                $barRect = SvgBuilder::rect(
                    $offsetX + $pos['x'],
                    $offsetY + $pos['y'] + 25,
                    $pos['width'],
                    $pos['height'],
                    [
                        'fill' => $bar->getBackgroundColor(),
                        'rx' => 3,
                        'ry' => 3,
                        'class' => 'bar',
                    ]
                );
                $barsGroup->appendChild($barRect);
                
                // Bar title
                $barText = SvgBuilder::text(
                    $bar->getTitle(),
                    $offsetX + $pos['x'] + 5,
                    $offsetY + $pos['y'] + 25 + ($barHeight / 2),
                    [
                        'fill' => $bar->getColor() ?? '#ffffff',
                        'font-size' => 10,
                        'dominant-baseline' => 'middle',
                    ]
                );
                $barsGroup->appendChild($barText);
            }
        }
        
        return $barsGroup;
    }
}
