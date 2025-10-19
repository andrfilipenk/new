<?php
// app/Eav/Storage/DatetimeStorageStrategy.php
namespace Eav\Storage;

use DateTime;

/**
 * Datetime Storage Strategy
 * 
 * Handles storage of datetime values
 */
class DatetimeStorageStrategy extends AbstractStorageStrategy
{
    public function __construct($db, string $tableName)
    {
        parent::__construct($db, $tableName, 'datetime');
    }

    public function validateValue(mixed $value): bool
    {
        if ($value instanceof DateTime) {
            return true;
        }

        if (is_string($value)) {
            return strtotime($value) !== false;
        }

        return false;
    }

    public function transformForStorage(mixed $value): string
    {
        if ($value instanceof DateTime) {
            return $value->format('Y-m-d H:i:s');
        }

        $date = new DateTime($value);
        return $date->format('Y-m-d H:i:s');
    }

    public function transformFromStorage(mixed $value): DateTime
    {
        return new DateTime($value);
    }
}
