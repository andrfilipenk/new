<?php

namespace Core\Calendar\Renderers;

use Core\Calendar\CalendarConfig;
use Core\Calendar\Models\CalendarData;
use Core\Calendar\Models\Dimensions;
use Core\Calendar\Svg\SvgBuilder;
use Core\Calendar\Svg\SvgElement;
use Core\Calendar\Utilities\DateCalculator;
use Core\Calendar\Utilities\DimensionCalculator;
use DateTimeImmutable;

/**
 * Renderer for year view (12 mini-month calendars in grid)
 */
class YearViewRenderer extends AbstractViewRenderer
{
    public function calculateDimensions(CalendarConfig $config): Dimensions
    {
        $columns = $config->getOption('columns', 4);
        $monthWidth = $config->getOption('monthWidth', 250);
        $monthHeight = $config->getOption('monthHeight', 200);
        $headerHeight = $config->getOption('headerHeight', 60);
        
        return DimensionCalculator::calculateYearViewDimensions(
            $columns,
            $monthWidth,
            $monthHeight,
            $headerHeight
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
        
        // Year title
        $year = $config->getStartDate()->format('Y');
        $titleText = $this->createThemedText(
            $year,
            $dimensions->getWidth() / 2,
            35,
            $config,
            [
                'text-anchor' => 'middle',
                'font-size' => 24,
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
        
        $columns = $config->getOption('columns', 4);
        $monthWidth = $dimensions->getCellWidth();
        $monthHeight = $dimensions->getCellHeight();
        $offsetY = $dimensions->getHeaderHeight();
        $year = (int)$config->getStartDate()->format('Y');
        
        // Render 12 mini-months
        for ($month = 1; $month <= 12; $month++) {
            $row = (int)(($month - 1) / $columns);
            $col = ($month - 1) % $columns;
            
            $x = 20 + ($col * $monthWidth);
            $y = $offsetY + ($row * $monthHeight);
            
            $miniMonth = $this->renderMiniMonth(
                $config,
                $year,
                $month,
                $x,
                $y,
                $monthWidth - 10,
                $monthHeight - 10,
                $data
            );
            
            $bodyGroup->appendChild($miniMonth);
        }
        
        return $bodyGroup;
    }

    protected function renderBars(CalendarConfig $config, CalendarData $data, Dimensions $dimensions): ?SvgElement
    {
        // Bars are rendered within each mini-month
        return null;
    }

    private function renderMiniMonth(
        CalendarConfig $config,
        int $year,
        int $month,
        float $x,
        float $y,
        float $width,
        float $height,
        CalendarData $data
    ): SvgElement {
        $theme = $this->getTheme($config);
        $monthGroup = SvgBuilder::group(['class' => 'mini-month']);
        
        // Month background
        $monthBg = SvgBuilder::rect(
            $x,
            $y,
            $width,
            $height,
            [
                'fill' => $theme->getBackgroundColor(),
                'stroke' => $theme->getBorderColor(),
                'stroke-width' => 1,
                'rx' => 4,
            ]
        );
        $monthGroup->appendChild($monthBg);
        
        // Month name
        $monthName = DateCalculator::getMonthName($month, $config->getLocale(), true);
        $monthTitle = $this->createThemedText(
            $monthName,
            $x + ($width / 2),
            $y + 15,
            $config,
            [
                'text-anchor' => 'middle',
                'font-size' => 12,
                'font-weight' => 'bold',
            ]
        );
        $monthGroup->appendChild($monthTitle);
        
        // Day headers
        $cellWidth = ($width - 20) / 7;
        $cellHeight = ($height - 40) / 6;
        
        for ($i = 0; $i < 7; $i++) {
            $dayNum = ($config->getFirstDayOfWeek() + $i) % 7;
            $dayName = DateCalculator::getDayName($dayNum, $config->getLocale(), true);
            $dayName = substr($dayName, 0, 1); // First letter only
            
            $dayX = $x + 10 + ($i * $cellWidth) + ($cellWidth / 2);
            $dayY = $y + 30;
            
            $dayText = $this->createThemedText(
                $dayName,
                $dayX,
                $dayY,
                $config,
                [
                    'text-anchor' => 'middle',
                    'font-size' => 8,
                    'fill' => $theme->getSecondaryTextColor(),
                ]
            );
            $monthGroup->appendChild($dayText);
        }
        
        // Render days
        $date = new DateTimeImmutable("{$year}-{$month}-01");
        $firstDay = DateCalculator::getFirstDayOfMonth($date);
        $lastDay = DateCalculator::getLastDayOfMonth($date);
        
        $currentDate = DateCalculator::getFirstDayOfWeek($firstDay, $config->getFirstDayOfWeek());
        $endDate = DateCalculator::getLastDayOfWeek($lastDay, $config->getFirstDayOfWeek());
        
        $today = new DateTimeImmutable();
        $weekIndex = 0;
        $dayIndex = 0;
        
        while ($currentDate <= $endDate) {
            $isCurrentMonth = (int)$currentDate->format('n') === $month;
            $isToday = DateCalculator::isToday($currentDate);
            
            $cellX = $x + 10 + ($dayIndex * $cellWidth);
            $cellY = $y + 40 + ($weekIndex * $cellHeight);
            
            // Day cell background (only for today)
            if ($isToday) {
                $todayBg = SvgBuilder::rect(
                    $cellX,
                    $cellY,
                    $cellWidth,
                    $cellHeight,
                    [
                        'fill' => $theme->getTodayColor(),
                        'opacity' => 0.5,
                    ]
                );
                $monthGroup->appendChild($todayBg);
            }
            
            // Day number
            if ($isCurrentMonth) {
                $dayNum = $currentDate->format('j');
                $textColor = $isToday ? $theme->getTextColor() : $theme->getSecondaryTextColor();
                
                $dayText = $this->createThemedText(
                    $dayNum,
                    $cellX + ($cellWidth / 2),
                    $cellY + ($cellHeight / 2),
                    $config,
                    [
                        'text-anchor' => 'middle',
                        'dominant-baseline' => 'middle',
                        'font-size' => 8,
                        'fill' => $textColor,
                        'font-weight' => $isToday ? 'bold' : 'normal',
                    ]
                );
                $monthGroup->appendChild($dayText);
                
                // Show event indicator if data exists
                if ($data->hasDataForDate($currentDate)) {
                    $indicator = SvgBuilder::circle(
                        $cellX + ($cellWidth / 2),
                        $cellY + $cellHeight - 3,
                        2,
                        [
                            'fill' => $theme->getPrimaryColor(),
                        ]
                    );
                    $monthGroup->appendChild($indicator);
                }
            }
            
            $dayIndex++;
            if ($dayIndex === 7) {
                $dayIndex = 0;
                $weekIndex++;
            }
            
            $currentDate = $currentDate->modify('+1 day');
        }
        
        return $monthGroup;
    }
}
