<?php
// app/Core/Eav/Storage/ValueTransformer.php
namespace Core\Eav\Storage;

use DateTime;
use DateTimeInterface;

/**
 * Handles value transformation between PHP and database formats
 */
class ValueTransformer
{
    /**
     * Transform PHP value to database format
     */
    public function toDatabase(mixed $value, string $backendType): mixed
    {
        if ($value === null) {
            return null;
        }

        switch ($backendType) {
            case 'varchar':
            case 'text':
                return (string) $value;

            case 'int':
                return (int) $value;

            case 'decimal':
                return is_numeric($value) ? number_format((float) $value, 4, '.', '') : null;

            case 'datetime':
                return $this->formatDateTime($value);

            default:
                return $value;
        }
    }

    /**
     * Transform database value to PHP format
     */
    public function fromDatabase(mixed $value, string $backendType): mixed
    {
        if ($value === null) {
            return null;
        }

        switch ($backendType) {
            case 'varchar':
            case 'text':
                return (string) $value;

            case 'int':
                return (int) $value;

            case 'decimal':
                return (float) $value;

            case 'datetime':
                return $value; // Keep as string, can be converted to DateTime by application

            default:
                return $value;
        }
    }

    /**
     * Format datetime value for database
     */
    private function formatDateTime(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_string($value)) {
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return date('Y-m-d H:i:s', $timestamp);
            }
        }

        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', (int) $value);
        }

        return null;
    }

    /**
     * Validate value type matches backend type
     */
    public function validate(mixed $value, string $backendType): bool
    {
        if ($value === null) {
            return true;
        }

        switch ($backendType) {
            case 'varchar':
            case 'text':
                return is_string($value) || is_numeric($value);

            case 'int':
                return is_numeric($value) && (string)(int)$value === (string)$value;

            case 'decimal':
                return is_numeric($value);

            case 'datetime':
                return $value instanceof DateTimeInterface || 
                       (is_string($value) && strtotime($value) !== false);

            default:
                return true;
        }
    }
}
