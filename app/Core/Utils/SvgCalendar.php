<?php
namespace Core\Utils;

class SvgCalendar
{
    private \Core\Utils\Url $url;
    private \DateTimeImmutable $date;

    public function __construct(\Core\Utils\Url $url, string $date = null)
    {
        $this->url = $url;
        $this->date = $date ? Dates::createFromString($date) : Dates::today();
    }

    public function generateMonthSvg(int $width = 350, int $height = 250): string
    {
        $start = $this->date->modify('first day of this month');
        $end = $start->modify('last day of this month +1 day');
        $firstWeekDay = (int) $start->format('N') - 1; // 0 (Monday) to 6 (Sunday)
        
        // Calculate weeks needed (including padding for first week)
        $daysInMonth = (int) $start->format('t');
        $weeks = ceil(($daysInMonth + $firstWeekDay) / 7);
        
        // SVG dimensions
        $cellWidth = $width / 7;
        $cellHeight = ($height - 20) / ($weeks + 1); // +1 for header
        $svg = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';
        
        // Header: Day names (M, T, W, T, F, S, S)
        $days = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
        foreach ($days as $i => $day) {
            $x = $i * $cellWidth;
            $svg .= '<text x="' . ($x + $cellWidth / 2) . '" y="15" text-anchor="middle" class="day-header">' . $day . '</text>';
        }
        
        // Calendar days
        $current = $start;
        $dayCount = -$firstWeekDay; // Start before first day for padding
        for ($week = 0; $week < $weeks; $week++) {
            for ($day = 0; $day < 7; $day++) {
                $x = $day * $cellWidth;
                $y = ($week + 1) * $cellHeight + 20; // Offset for header
                $dayCount++;
                
                if ($dayCount > 0 && $dayCount <= $daysInMonth) {
                    $dateStr = $current->format('Y-m-d');
                    $isWeekend = $day >= 5; // Saturday (5) or Sunday (6)
                    $class = $isWeekend ? 'weekend' : 'weekday';
                    
                    // Clickable link
                    $url = $this->url->get('calendar', ['date' => $dateStr]);
                    $svg .= '<a href="' . htmlspecialchars($url) . '">';
                    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $cellWidth . '" height="' . $cellHeight . '" class="' . $class . '"/>';
                    $svg .= '<text x="' . ($x + $cellWidth / 2) . '" y="' . ($y + $cellHeight / 2) . '" text-anchor="middle" class="day-text">' . $dayCount . '</text>';
                    $svg .= '</a>';
                    $current = $current->modify('+1 day');
                } else {
                    // Empty cell
                    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $cellWidth . '" height="' . $cellHeight . '" class="empty"/>';
                }
            }
        }
        
        $svg .= '</svg>';
        return $svg;
    }
}