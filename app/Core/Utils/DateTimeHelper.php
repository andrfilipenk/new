<?php

namespace Core\Utils;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class DateTimeHelper
{
    public static function createFromFormat(string $format, string $datetime, ?string $timezone = null): ?DateTimeImmutable
    {
        try {
            $tz = $timezone ? new DateTimeZone($timezone) : null;
            $result = DateTimeImmutable::createFromFormat($format, $datetime, $tz);
            return $result ?: null;
        } catch (\Exception) {
            return null;
        }
    }

    public static function createFromString(string $datetime, ?string $timezone = null): ?DateTimeImmutable
    {
        try {
            $tz = $timezone ? new DateTimeZone($timezone) : null;
            return new DateTimeImmutable($datetime, $tz);
        } catch (\Exception) {
            return null;
        }
    }

    public static function formatForInput(DateTimeInterface $datetime, string $type = 'datetime'): string
    {
        return match ($type) {
            'date' => $datetime->format('Y-m-d'),
            'time' => $datetime->format('H:i'),
            default => $datetime->format('Y-m-d\TH:i'),
        };
    }

    public static function humanReadable(DateTimeInterface $datetime): string
    {
        $diff = (new DateTimeImmutable())->diff($datetime);
        
        $periods = ['y' => 'year', 'm' => 'month', 'd' => 'day', 'h' => 'hour', 'i' => 'minute'];
        
        foreach ($periods as $key => $period) {
            if ($diff->$key > 0) {
                $unit = $period . ($diff->$key > 1 ? 's' : '');
                return $diff->invert ? "{$diff->$key} {$unit} ago" : "in {$diff->$key} {$unit}";
            }
        }
        
        return 'Just now';
    }

    public static function timezoneSelect(string $name, ?string $selected = null, array $attributes = []): string
    {
        $options = [];
        foreach (DateTimeZone::listIdentifiers() as $tz) {
            $options[] = Tag::option($tz, [
                'value' => $tz,
                'selected' => $selected === $tz
            ]);
        }
        
        return Tag::select($options, array_merge($attributes, ['name' => $name]));
    }

    public static function isValidFormat(string $datetime, string $format): bool
    {
        $d = self::createFromFormat($format, $datetime);
        return $d && $d->format($format) === $datetime;
    }
}