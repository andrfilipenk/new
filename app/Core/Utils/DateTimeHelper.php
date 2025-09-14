<?php

namespace Core\Utils;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;

class DateTimeHelper
{
    /**
     * Creates a DateTimeImmutable object from a specific format.
     */
    public static function createFromFormat(string $format, string $datetime, ?string $timezone = null): ?DateTimeImmutable
    {
        try {
            $tz = $timezone ? new DateTimeZone($timezone) : null;
            return DateTimeImmutable::createFromFormat($format, $datetime, $tz);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Creates a DateTimeImmutable object from a string.
     */
    public static function createFromString(string $datetime, ?string $timezone = null): ?DateTimeImmutable
    {
        try {
            $tz = $timezone ? new DateTimeZone($timezone) : null;
            return new DateTimeImmutable($datetime, $tz);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Formats a DateTime object for HTML input fields.
     */
    public static function formatForInput(DateTimeInterface $datetime, string $type = 'datetime'): string
    {
        return match ($type) {
            'date' => $datetime->format('Y-m-d'),
            'time' => $datetime->format('H:i'),
            default => $datetime->format('Y-m-d\TH:i'),
        };
    }

    /**
     * Formats a DateTime object into a human-readable "time ago" or "from now" string.
     */
    public static function humanReadable(DateTimeInterface $datetime): string
    {
        $now = new DateTimeImmutable();
        $diff = $now->diff($datetime);

        $periods = [
            'y' => 'year',
            'm' => 'month',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        ];

        foreach ($periods as $key => $period) {
            if ($diff->$key > 0) {
                $value = $diff->$key;
                $unit = $period . ($value > 1 ? 's' : '');
                return $diff->invert ? "$value $unit ago" : "in $value $unit";
            }
        }

        return 'Just now';
    }

    /**
     * Generates an HTML select dropdown for timezones using the Tag helper.
     */
    public static function timezoneSelect(string $name, ?string $selected = null, array $attributes = []): Tag
    {
        $options = [];
        foreach (self::getTimezoneList() as $value => $label) {
            $optionAttrs = ['value' => $value];
            if ($selected === $value) {
                $optionAttrs['selected'] = true;
            }
            $options[] = Tag::option($label, $optionAttrs);
        }

        $selectAttributes = array_merge($attributes, ['name' => $name]);
        return Tag::select($options, $selectAttributes);
    }

    /**
     * Returns an array of all timezone identifiers.
     */
    public static function getTimezoneList(): array
    {
        return DateTimeZone::listIdentifiers();
    }

    /**
     * Validates if a string matches a given date format.
     */
    public static function isValidFormat(string $datetime, string $format): bool
    {
        $d = self::createFromFormat($format, $datetime);
        return $d && $d->format($format) === $datetime;
    }
}