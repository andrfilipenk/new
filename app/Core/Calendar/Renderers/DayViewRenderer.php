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
 * Renderer for day view (single day with hourly time slots)
 */
class DayViewRenderer extends AbstractViewRenderer
{
    public function calculateDimensions(CalendarConfig $config): Dimensions
    {
        $startHour = $config->getOption('startHour', 0);
        $endHour = $config->getOption('endHour', 24);
        $hourHeight = $config->getOption('hourHeight', 60);
        $headerHeight = $config->getOption('headerHeight', 60);
        $timeAxisWidth = $config->getOption('timeAxisWidth', 60);
        
        return DimensionCalculator::calculateDayViewDimensions(
            $startHour,
            $endHour,
            $hourHeight,
            $headerHeight,
            $timeAxisWidth
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
        
        // Date title
        $date = DateTimeImmutable::createFromInterface($config->getStartDate());
        $dayName = DateCalculator::getDayName((int)$date->format('w'), $config->getLocale());
        $title = $dayName . ', ' . $date->format('F j, Y');
        
        $titleText = $this->createThemedText(
            $title,
            $dimensions->getWidth() / 2,
            30,
            $config,
            [
                'text-anchor' => 'middle',
                'font-size' => 18,
                'font-weight' => 'bold',
            ]
        );
        $headerGroup->appendChild($titleText);
        
        return $headerGroup;
    }

    protected function renderBody(CalendarConfig $config, CalendarData $data, Dimensions $dimensions): ?SvgElement
    {
        $bodyGroup = SvgBuilder::group(['class' => 'body']);
        $theme = $this->getTheme($config);
        
        $startHour = $config->getOption('startHour', 0);
        $endHour = $config->getOption('endHour', 24);
        $hourHeight = $config->getOption('hourHeight', 60);
        $timeAxisWidth = $config->getOption('timeAxisWidth', 60);
        $timeFormat = $config->getOption('timeFormat', '24h');
        
        $offsetY = $dimensions->getHeaderHeight();
        $offsetX = $timeAxisWidth + 10;
        $contentWidth = $dimensions->getWidth() - $timeAxisWidth - 20;
        
        // Render time grid
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
                    'font-size' => 12,
                    'fill' => $theme->getSecondaryTextColor(),
                ]
            );
            $bodyGroup->appendChild($timeText);
            
            // Day column background
            $cellRect = $this->createThemedRect(
                $offsetX,
                $y,
                $contentWidth,
                $hourHeight,
                $config,
                [
                    'fill' => 'transparent',
                    'class' => 'time-slot',
                ]
            );
            $bodyGroup->appendChild($cellRect);
        }
        
        return $bodyGroup;
    }

    protected function renderBars(CalendarConfig $config, CalendarData $data, Dimensions $dimensions): ?SvgElement
    {
        if ($data->isEmpty()) {
            return null;
        }
        
        $barsGroup = SvgBuilder::group(['class' => 'bars']);
        
        $startHour = $config->getOption('startHour', 0);
        $endHour = $config->getOption('endHour', 24);
        $hourHeight = $config->getOption('hourHeight', 60);
        $timeAxisWidth = $config->getOption('timeAxisWidth', 60);
        
        $offsetY = $dimensions->getHeaderHeight();
        $offsetX = $timeAxisWidth + 10;
        $contentWidth = $dimensions->getWidth() - $timeAxisWidth - 20;
        
        foreach ($data->getSortedBars() as $bar) {
            $position = PositionCalculator::calculateTimeBasedPosition(
                $bar,
                $config->getStartDate(),
                0, // Single day column
                $startHour,
                $endHour,
                $hourHeight,
                $contentWidth,
                $offsetX,
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
                    'rx' => 4,
                    'ry' => 4,
                    'class' => 'bar',
                ]
            );
            $barsGroup->appendChild($barRect);
            
            // Bar title
            $barText = SvgBuilder::text(
                $bar->getTitle(),
                $position['x'] + 5,
                $position['y'] + 15,
                [
                    'fill' => $bar->getColor() ?? '#ffffff',
                    'font-size' => 12,
                    'font-weight' => 'bold',
                ]
            );
            $barsGroup->appendChild($barText);
            
            // Time range
            $timeRange = $bar->getStartDate()->format('H:i') . ' - ' . $bar->getEndDate()->format('H:i');
            $timeText = SvgBuilder::text(
                $timeRange,
                $position['x'] + 5,
                $position['y'] + 30,
                [
                    'fill' => $bar->getColor() ?? '#ffffff',
                    'font-size' => 10,
                ]
            );
            $barsGroup->appendChild($timeText);
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
            return $displayHour . ':00 ' . $period;
        }
        
        return sprintf('%02d:00', $hour);
    }
}
