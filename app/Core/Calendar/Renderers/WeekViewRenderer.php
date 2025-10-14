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
 * Renderer for week view (7 days with hourly time slots)
 */
class WeekViewRenderer extends AbstractViewRenderer
{
    public function calculateDimensions(CalendarConfig $config): Dimensions
    {
        $startHour = $config->getOption('startHour', 6);
        $endHour = $config->getOption('endHour', 22);
        $hourHeight = $config->getOption('hourHeight', 40);
        $headerHeight = $config->getOption('headerHeight', 60);
        $timeAxisWidth = $config->getOption('timeAxisWidth', 60);
        $dayColumnWidth = $config->getOption('dayColumnWidth', 120);
        
        return DimensionCalculator::calculateWeekViewDimensions(
            $startHour,
            $endHour,
            $hourHeight,
            $headerHeight,
            $timeAxisWidth,
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
        
        // Week title
        $startDate = DateTimeImmutable::createFromInterface($config->getStartDate());
        $endDate = DateTimeImmutable::createFromInterface($config->getEndDate());
        $weekNum = DateCalculator::getWeekNumber($startDate);
        $title = "Week {$weekNum} - " . $startDate->format('M j') . ' to ' . $endDate->format('M j, Y');
        
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
        $timeAxisWidth = $config->getOption('timeAxisWidth', 60);
        $dayColumnWidth = $dimensions->getCellWidth();
        
        $currentDate = DateCalculator::getFirstDayOfWeek($startDate, $config->getFirstDayOfWeek());
        $today = new DateTimeImmutable();
        
        for ($i = 0; $i < 7; $i++) {
            $x = $timeAxisWidth + ($i * $dayColumnWidth);
            $isToday = DateCalculator::isToday($currentDate);
            
            // Column background
            if ($isToday) {
                $colBg = SvgBuilder::rect(
                    $x,
                    40,
                    $dayColumnWidth,
                    20,
                    [
                        'fill' => $theme->getTodayColor(),
                        'opacity' => 0.3,
                    ]
                );
                $headerGroup->appendChild($colBg);
            }
            
            // Day name and date
            $dayName = DateCalculator::getDayName((int)$currentDate->format('w'), $config->getLocale(), true);
            $dayNum = $currentDate->format('j');
            $label = $dayName . ' ' . $dayNum;
            
            $dayText = $this->createThemedText(
                $label,
                $x + ($dayColumnWidth / 2),
                50,
                $config,
                [
                    'text-anchor' => 'middle',
                    'font-size' => 12,
                    'font-weight' => $isToday ? 'bold' : 'normal',
                ]
            );
            $headerGroup->appendChild($dayText);
            
            $currentDate = $currentDate->modify('+1 day');
        }
        
        return $headerGroup;
    }

    protected function renderBody(CalendarConfig $config, CalendarData $data, Dimensions $dimensions): ?SvgElement
    {
        $bodyGroup = SvgBuilder::group(['class' => 'body']);
        $theme = $this->getTheme($config);
        
        $startHour = $config->getOption('startHour', 6);
        $endHour = $config->getOption('endHour', 22);
        $hourHeight = $config->getOption('hourHeight', 40);
        $timeAxisWidth = $config->getOption('timeAxisWidth', 60);
        $dayColumnWidth = $dimensions->getCellWidth();
        $highlightWeekend = $config->getOption('highlightWeekend', true);
        $highlightToday = $config->getOption('highlightToday', true);
        
        $offsetY = $dimensions->getHeaderHeight();
        $startDate = DateTimeImmutable::createFromInterface($config->getStartDate());
        $currentDate = DateCalculator::getFirstDayOfWeek($startDate, $config->getFirstDayOfWeek());
        $today = new DateTimeImmutable();
        
        // Render day columns
        for ($dayCol = 0; $dayCol < 7; $dayCol++) {
            $x = $timeAxisWidth + ($dayCol * $dayColumnWidth);
            $isWeekend = DateCalculator::isWeekend($currentDate);
            $isToday = DateCalculator::isToday($currentDate);
            
            // Column background
            $colAttrs = ['class' => 'day-column'];
            if ($highlightWeekend && $isWeekend) {
                $colAttrs['fill'] = $theme->getWeekendColor();
            }
            if ($highlightToday && $isToday) {
                $colAttrs['fill'] = $theme->getTodayColor();
                $colAttrs['opacity'] = 0.2;
            }
            
            if (isset($colAttrs['fill'])) {
                $colBg = SvgBuilder::rect(
                    $x,
                    $offsetY,
                    $dayColumnWidth,
                    ($endHour - $startHour) * $hourHeight,
                    $colAttrs
                );
                $bodyGroup->appendChild($colBg);
            }
            
            // Vertical separator
            $separator = SvgBuilder::line(
                $x,
                $offsetY,
                $x,
                $offsetY + (($endHour - $startHour) * $hourHeight),
                [
                    'stroke' => $theme->getBorderColor(),
                    'stroke-width' => 1,
                ]
            );
            $bodyGroup->appendChild($separator);
            
            $currentDate = $currentDate->modify('+1 day');
        }
        
        // Render time grid
        $timeFormat = $config->getOption('timeFormat', '24h');
        
        for ($hour = $startHour; $hour < $endHour; $hour++) {
            $y = $offsetY + (($hour - $startHour) * $hourHeight);
            
            // Hour line
            $line = SvgBuilder::line(
                $timeAxisWidth,
                $y,
                $dimensions->getWidth() - 10,
                $y,
                [
                    'stroke' => $theme->getBorderColor(),
                    'stroke-width' => 1,
                ]
            );
            $bodyGroup->appendChild($line);
            
            // Time label
            $timeLabel = $this->formatHour($hour, $timeFormat);
            $timeText = $this->createThemedText(
                $timeLabel,
                $timeAxisWidth - 10,
                $y + 5,
                $config,
                [
                    'text-anchor' => 'end',
                    'font-size' => 10,
                    'fill' => $theme->getSecondaryTextColor(),
                ]
            );
            $bodyGroup->appendChild($timeText);
        }
        
        return $bodyGroup;
    }

    protected function renderBars(CalendarConfig $config, CalendarData $data, Dimensions $dimensions): ?SvgElement
    {
        if ($data->isEmpty()) {
            return null;
        }
        
        $barsGroup = SvgBuilder::group(['class' => 'bars']);
        
        $startHour = $config->getOption('startHour', 6);
        $endHour = $config->getOption('endHour', 22);
        $hourHeight = $config->getOption('hourHeight', 40);
        $timeAxisWidth = $config->getOption('timeAxisWidth', 60);
        $dayColumnWidth = $dimensions->getCellWidth();
        
        $offsetY = $dimensions->getHeaderHeight();
        $startDate = DateTimeImmutable::createFromInterface($config->getStartDate());
        $weekStartDate = DateCalculator::getFirstDayOfWeek($startDate, $config->getFirstDayOfWeek());
        
        foreach ($data->getSortedBars() as $bar) {
            // Calculate which day column(s) the bar belongs to
            $barStart = $bar->getStartDate()->setTime(0, 0, 0);
            $daysDiff = $weekStartDate->diff($barStart)->days;
            
            if ($daysDiff >= 0 && $daysDiff < 7) {
                $position = PositionCalculator::calculateTimeBasedPosition(
                    $bar,
                    $weekStartDate,
                    $daysDiff,
                    $startHour,
                    $endHour,
                    $hourHeight,
                    $dayColumnWidth,
                    $timeAxisWidth,
                    $offsetY
                );
                
                // Bar rectangle
                $barRect = SvgBuilder::rect(
                    $position['x'],
                    $position['y'],
                    $position['width'],
                    $position['height'],
                    [
                        'fill' => $bar->getBackgroundColor(),
                        'rx' => 3,
                        'ry' => 3,
                        'class' => 'bar',
                    ]
                );
                $barsGroup->appendChild($barRect);
                
                // Bar title (if height allows)
                if ($position['height'] >= 20) {
                    $barText = SvgBuilder::text(
                        $bar->getTitle(),
                        $position['x'] + 3,
                        $position['y'] + 12,
                        [
                            'fill' => $bar->getColor() ?? '#ffffff',
                            'font-size' => 10,
                        ]
                    );
                    $barsGroup->appendChild($barText);
                }
            }
        }
        
        return $barsGroup;
    }

    private function formatHour(int $hour, string $format): string
    {
        if ($format === '12h') {
            $period = $hour >= 12 ? 'PM' : 'AM';
            $displayHour = $hour % 12;
            if ($displayHour === 0) {
                $displayHour = 12;
            }
            return $displayHour . ' ' . $period;
        }
        
        return sprintf('%02d:00', $hour);
    }
}
