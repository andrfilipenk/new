<?php
// app/Core/Utils/Dates.php
namespace Core\Utils;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class Dates
{
    public static function today(string $timezone = null)
    {
        $tz = $timezone ? new DateTimeZone($timezone) : null;
        return new DateTimeImmutable('now', $tz);
    }

    public static function createFromFormat(string $format, string $datetime, string $timezone = null)
    {
        try {
            $tz = $timezone ? new DateTimeZone($timezone) : null;
            $result = DateTimeImmutable::createFromFormat($format, $datetime, $tz);
            return $result ?: null;
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Undocumented function
     *
     * @param string $datetime
     * @param string|null $timezone
     * @return DateTimeImmutable|null
     */
    public static function createFromString(string $datetime, string $timezone = null)
    {
        try {
            $tz = $timezone ? new DateTimeZone($timezone) : null;
            return new DateTimeImmutable($datetime, $tz);
        } catch (\Exception) {
            return null;
        }
    }

    public static function formatForInput(DateTimeInterface $datetime, string $type = 'datetime')
    {
        return match ($type) {
            'date' => $datetime->format('Y-m-d'),
            'time' => $datetime->format('H:i'),
            default => $datetime->format('Y-m-d\TH:i'),
        };
    }

    public static function humanReadable(DateTimeInterface $datetime)
    {
        $diff = (new DateTimeImmutable())->diff($datetime);
        $periods = [
            'y' => 'year', 
            'm' => 'month', 
            'd' => 'day', 
            'h' => 'hour', 
            'i' => 'minute'
        ];
        foreach ($periods as $key => $period) {
            if ($diff->$key > 0) {
                $unit = $period . ($diff->$key > 1 ? 's' : '');
                return $diff->invert ? "{$diff->$key} {$unit} ago" : "in {$diff->$key} {$unit}";
            }
        }
        return 'Just now';
    }

    public static function timezoneSelect(string $name, string $selected = null, array $attributes = [])
    {
        $options = [];
        foreach (DateTimeZone::listIdentifiers() as $tz) {
            $options[] = Tag::option($tz, [
                'value'     => $tz,
                'selected'  => $selected === $tz
            ]);
        }
        return Tag::select($options, array_merge($attributes, ['name' => $name]));
    }

    public static function isValidFormat(string $datetime, string $format)
    {
        $d = self::createFromFormat($format, $datetime);
        return $d && $d->format($format) === $datetime;
    }


    public static function getMonthDateRange(DateTimeImmutable $dti): array
    {
        $start = $dti->modify('first day of this month');
        $end = $start->modify('first day of next month');
        $dates = [];
        $current = $start;
        while ($current < $end) {
            $dayOfWeek = $current->format('N'); // 1 (Monday) to 7 (Sunday)
            $dates[] = [
                'date' => $current->format('Y-m-d'),
                'dayName' => $current->format('l'),
                'isWeekend' => $dayOfWeek >= 6 // Saturday (6) or Sunday (7)
            ];
            $current = $current->modify('+1 day');
        }
        return $dates;
    }

    public static function getYearDateRange(): array
    {
        $start = Dates::today()->modify('first day of January this year');
        $end = Dates::today()->modify('last day of December this year +1 day');
        
        $dates = [];
        $current = $start;
        
        while ($current < $end) {
            $dayOfWeek = $current->format('N');
            $dates[] = [
                'date' => $current->format('Y-m-d'),
                'dayName' => $current->format('l'),
                'isWeekend' => $dayOfWeek >= 6
            ];
            $current = $current->modify('+1 day');
        }
        
        return $dates;
    }
}