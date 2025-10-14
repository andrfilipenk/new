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
 * Renderer for month view
 */
class MonthViewRenderer extends AbstractViewRenderer
{
    public function calculateDimensions(CalendarConfig $config): Dimensions
    {
        $startDate = DateTimeImmutable::createFromInterface($config->getStartDate());
        $weeks = DateCalculator::getWeeksInMonth($startDate, $config->getFirstDayOfWeek());
        $weekCount = count($weeks);
        
        $cellHeight = $config->getOption('cellHeight', 100);
        $cellWidth = $config->getOption('cellWidth', 120);
        $headerHeight = $config->getOption('headerHeight', 80);
        $weekNumberWidth = $config->getOption('weekNumberWidth', 40);
        
        return DimensionCalculator::calculateMonthViewDimensions(
            $weekCount,
            $cellHeight,
            $headerHeight,
            $weekNumberWidth,
            $cellWidth,
            $config->showWeekNumbers()
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
        
        // Month and year title
        $startDate = DateTimeImmutable::createFromInterface($config->getStartDate());
        $monthName = DateCalculator::getMonthName((int)$startDate->format('n'), $config->getLocale());
        $year = $startDate->format('Y');
        $title = "{$monthName} {$year}";
        
        $titleText = $this->createThemedText(
            $title,
            $dimensions->getWidth() / 2,
            30,
            $config,
            [
                'text-anchor' => 'middle',
                'font-size' => 20,
                'font-weight' => 'bold',
            ]
        );
        $headerGroup->appendChild($titleText);
        
        // Day names
        $weekNumWidth = $config->showWeekNumbers() ? $config->getOption('weekNumberWidth', 40) : 0;
        $dayStartX = $weekNumWidth + 10;
        $cellWidth = $dimensions->getCellWidth();
        
        for ($i = 0; $i < 7; $i++) {
            $dayNum = ($config->getFirstDayOfWeek() + $i) % 7;
            $dayName = DateCalculator::getDayName($dayNum, $config->getLocale(), true);
            
            $x = $dayStartX + ($i * $cellWidth) + ($cellWidth / 2);
            $y = 55;
            
            $dayText = $this->createThemedText(
                $dayName,
                $x,
                $y,
                $config,
                [
                    'text-anchor' => 'middle',
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
        
        $startDate = DateTimeImmutable::createFromInterface($config->getStartDate());
        $weeks = DateCalculator::getWeeksInMonth($startDate, $config->getFirstDayOfWeek());
        
        $weekNumWidth = $config->showWeekNumbers() ? $config->getOption('weekNumberWidth', 40) : 0;
        $cellWidth = $dimensions->getCellWidth();
        $cellHeight = $dimensions->getCellHeight();
        $offsetY = $dimensions->getHeaderHeight();
        $offsetX = $weekNumWidth + 10;
        
        $month = (int)$startDate->format('n');
        $today = new DateTimeImmutable();
        
        foreach ($weeks as $weekIndex => $week) {
            // Render week number if enabled
            if ($config->showWeekNumbers()) {
                $weekNum = $week['weekNumber'];
                $weekText = $this->createThemedText(
                    (string)$weekNum,
                    $weekNumWidth / 2,
                    $offsetY + ($weekIndex * $cellHeight) + ($cellHeight / 2),
                    $config,
                    [
                        'text-anchor' => 'middle',
                        'dominant-baseline' => 'middle',
                        'font-size' => 12,
                        'fill' => $theme->getSecondaryTextColor(),
                    ]
                );
                $bodyGroup->appendChild($weekText);
            }
            
            // Render day cells
            $currentDate = $week['startDate'];
            for ($dayIndex = 0; $dayIndex < 7; $dayIndex++) {
                $x = $offsetX + ($dayIndex * $cellWidth);
                $y = $offsetY + ($weekIndex * $cellHeight);
                
                $isCurrentMonth = (int)$currentDate->format('n') === $month;
                $isWeekend = DateCalculator::isWeekend($currentDate);
                $isToday = DateCalculator::isToday($currentDate);
                
                // Cell background
                $cellAttrs = ['class' => 'cell'];
                if ($isWeekend) {
                    $cellAttrs['fill'] = $theme->getWeekendColor();
                    $cellAttrs['class'] .= ' weekend';
                }
                if ($isToday) {
                    $cellAttrs['fill'] = $theme->getTodayColor();
                    $cellAttrs['class'] .= ' today';
                }
                if (!$isCurrentMonth) {
                    $cellAttrs['opacity'] = '0.5';
                    $cellAttrs['class'] .= ' adjacent-month';
                }
                
                // Add click attributes
                if ($config->isClickable()) {
                    $clickAttrs = $this->getClickableAttrs($config, 'date', [
                        'date' => $currentDate->format('Y-m-d'),
                        'is-weekend' => $isWeekend ? '1' : '0',
                    ]);
                    $cellAttrs = array_merge($cellAttrs, $clickAttrs);
                }
                
                $cellRect = $this->createThemedRect($x, $y, $cellWidth, $cellHeight, $config, $cellAttrs);
                $bodyGroup->appendChild($cellRect);
                
                // Day number
                $dayNum = $currentDate->format('j');
                $textColor = $isCurrentMonth ? $theme->getTextColor() : $theme->getSecondaryTextColor();
                
                $dayText = $this->createThemedText(
                    $dayNum,
                    $x + 10,
                    $y + 20,
                    $config,
                    [
                        'fill' => $textColor,
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
        
        $startDate = DateTimeImmutable::createFromInterface($config->getStartDate());
        $weeks = DateCalculator::getWeeksInMonth($startDate, $config->getFirstDayOfWeek());
        
        // Build date grid
        $dateGrid = [];
        foreach ($weeks as $week) {
            $weekDates = [];
            $currentDate = $week['startDate'];
            for ($i = 0; $i < 7; $i++) {
                $weekDates[] = $currentDate;
                $currentDate = $currentDate->modify('+1 day');
            }
            $dateGrid[] = $weekDates;
        }
        
        $weekNumWidth = $config->showWeekNumbers() ? $config->getOption('weekNumberWidth', 40) : 0;
        $cellWidth = $dimensions->getCellWidth();
        $cellHeight = $dimensions->getCellHeight();
        $offsetY = $dimensions->getHeaderHeight();
        $offsetX = $weekNumWidth + 10;
        
        $barHeight = $config->getOption('barHeight', 20);
        $barSpacing = $config->getOption('barSpacing', 2);
        
        foreach ($data->getSortedBars() as $bar) {
            $positions = PositionCalculator::calculateDateBasedPosition(
                $bar,
                $dateGrid,
                $cellWidth,
                $cellHeight,
                $barHeight,
                $barSpacing
            );
            
            foreach ($positions as $pos) {
                $barRect = SvgBuilder::rect(
                    $offsetX + $pos['x'],
                    $offsetY + $pos['y'] + 25, // Offset for day number
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
                
                // Bar title (truncated if needed)
                $barText = SvgBuilder::text(
                    $bar->getTitle(),
                    $offsetX + $pos['x'] + 5,
                    $offsetY + $pos['y'] + 25 + ($barHeight / 2),
                    [
                        'fill' => $bar->getColor() ?? '#ffffff',
                        'font-size' => 11,
                        'dominant-baseline' => 'middle',
                    ]
                );
                $barsGroup->appendChild($barText);
            }
        }
        
        return $barsGroup;
    }
}
