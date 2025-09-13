<?php

namespace Core\Utils;

class DateTimeHelper
{
    public static function createFromFormat(string $format, string $datetime, string $timezone = null): ?\DateTimeImmutable
    {
        try {
            if ($timezone) {
                return \DateTimeImmutable::createFromFormat($format, $datetime, new \DateTimeZone($timezone));
            }
            return \DateTimeImmutable::createFromFormat($format, $datetime);
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function createFromString(string $datetime, string $timezone = null): ?\DateTimeImmutable
    {
        try {
            if ($timezone) {
                return new \DateTimeImmutable($datetime, new \DateTimeZone($timezone));
            }
            return new \DateTimeImmutable($datetime);
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function formatForInput(\DateTimeInterface $datetime, string $type = 'datetime'): string
    {
        switch ($type) {
            case 'date':
                return $datetime->format('Y-m-d');
            case 'time':
                return $datetime->format('H:i');
            case 'datetime':
            default:
                return $datetime->format('Y-m-d\TH:i');
        }
    }

    public static function isValidFormat(string $datetime, string $format): bool
    {
        $parsed = date_parse_from_format($format, $datetime);
        return $parsed['error_count'] === 0 && $parsed['warning_count'] === 0;
    }

    public static function getTimezoneList(): array
    {
        $timezones = [];
        $identifiers = \DateTimeZone::listIdentifiers();
        foreach ($identifiers as $identifier) {
            $timezones[$identifier] = str_replace('_', ' ', $identifier);
        }
        return $timezones;
    }
}