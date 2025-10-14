<?php

namespace Core\Calendar\Utilities;

use DateTimeInterface;
use DateTimeImmutable;
use DateInterval;

/**
 * Utility class for date calculations
 */
class DateCalculator
{
    /**
     * Get the first day of the month
     */
    public static function getFirstDayOfMonth(DateTimeInterface $date): DateTimeImmutable
    {
        $dt = DateTimeImmutable::createFromInterface($date);
        return $dt->modify('first day of this month')->setTime(0, 0, 0);
    }

    /**
     * Get the last day of the month
     */
    public static function getLastDayOfMonth(DateTimeInterface $date): DateTimeImmutable
    {
        $dt = DateTimeImmutable::createFromInterface($date);
        return $dt->modify('last day of this month')->setTime(23, 59, 59);
    }

    /**
     * Get the first day of the week for a given date
     */
    public static function getFirstDayOfWeek(DateTimeInterface $date, int $firstDayOfWeek = 0): DateTimeImmutable
    {
        $dt = DateTimeImmutable::createFromInterface($date);
        $dayOfWeek = (int)$dt->format('w'); // 0 (Sunday) to 6 (Saturday)
        
        $diff = ($dayOfWeek - $firstDayOfWeek + 7) % 7;
        return $dt->modify("-{$diff} days")->setTime(0, 0, 0);
    }

    /**
     * Get the last day of the week for a given date
     */
    public static function getLastDayOfWeek(DateTimeInterface $date, int $firstDayOfWeek = 0): DateTimeImmutable
    {
        $firstDay = self::getFirstDayOfWeek($date, $firstDayOfWeek);
        return $firstDay->modify('+6 days')->setTime(23, 59, 59);
    }

    /**
     * Get week number for a date (ISO-8601 week number)
     */
    public static function getWeekNumber(DateTimeInterface $date): int
    {
        $dt = DateTimeImmutable::createFromInterface($date);
        return (int)$dt->format('W');
    }

    /**
     * Get year for a week number (accounts for ISO-8601 year)
     */
    public static function getWeekYear(DateTimeInterface $date): int
    {
        $dt = DateTimeImmutable::createFromInterface($date);
        return (int)$dt->format('o'); // ISO-8601 year
    }

    /**
     * Get all dates in a date range
     * @return DateTimeImmutable[]
     */
    public static function getDatesInRange(DateTimeInterface $start, DateTimeInterface $end): array
    {
        $dates = [];
        $current = DateTimeImmutable::createFromInterface($start)->setTime(0, 0, 0);
        $endDate = DateTimeImmutable::createFromInterface($end)->setTime(0, 0, 0);
        
        while ($current <= $endDate) {
            $dates[] = $current;
            $current = $current->modify('+1 day');
        }
        
        return $dates;
    }

    /**
     * Get all weeks in a month
     * @return array Array of week information [weekNumber, year, startDate, endDate]
     */
    public static function getWeeksInMonth(DateTimeInterface $date, int $firstDayOfWeek = 0): array
    {
        $firstDay = self::getFirstDayOfMonth($date);
        $lastDay = self::getLastDayOfMonth($date);
        
        $weekStart = self::getFirstDayOfWeek($firstDay, $firstDayOfWeek);
        $weekEnd = self::getLastDayOfWeek($lastDay, $firstDayOfWeek);
        
        $weeks = [];
        $current = $weekStart;
        
        while ($current <= $weekEnd) {
            $weeks[] = [
                'weekNumber' => self::getWeekNumber($current),
                'year' => self::getWeekYear($current),
                'startDate' => $current,
                'endDate' => $current->modify('+6 days'),
            ];
            $current = $current->modify('+7 days');
        }
        
        return $weeks;
    }

    /**
     * Check if a date is a weekend
     */
    public static function isWeekend(DateTimeInterface $date): bool
    {
        $dayOfWeek = (int)$date->format('w');
        return $dayOfWeek === 0 || $dayOfWeek === 6;
    }

    /**
     * Check if a date is today
     */
    public static function isToday(DateTimeInterface $date): bool
    {
        $today = new DateTimeImmutable();
        $checkDate = DateTimeImmutable::createFromInterface($date);
        
        return $today->format('Y-m-d') === $checkDate->format('Y-m-d');
    }

    /**
     * Get day name for a day of week (0-6)
     */
    public static function getDayName(int $dayOfWeek, string $locale = 'en_US', bool $short = false): string
    {
        $days = [
            'en_US' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        ];
        
        $shortDays = [
            'en_US' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        ];
        
        $dayList = $short ? ($shortDays[$locale] ?? $shortDays['en_US']) : ($days[$locale] ?? $days['en_US']);
        return $dayList[$dayOfWeek] ?? '';
    }

    /**
     * Get month name
     */
    public static function getMonthName(int $month, string $locale = 'en_US', bool $short = false): string
    {
        $months = [
            'en_US' => ['January', 'February', 'March', 'April', 'May', 'June', 
                        'July', 'August', 'September', 'October', 'November', 'December'],
        ];
        
        $shortMonths = [
            'en_US' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 
                        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
        
        $monthList = $short ? ($shortMonths[$locale] ?? $shortMonths['en_US']) : ($months[$locale] ?? $months['en_US']);
        return $monthList[$month - 1] ?? '';
    }
}
