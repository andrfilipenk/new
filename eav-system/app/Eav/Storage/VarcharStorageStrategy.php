<?php
// app/Eav/Storage/VarcharStorageStrategy.php
namespace Eav\Storage;

/**
 * Varchar Storage Strategy
 * 
 * Handles storage of string/varchar values
 */
class VarcharStorageStrategy extends AbstractStorageStrategy
{
    private int $maxLength;

    public function __construct($db, string $tableName, int $maxLength = 255)
    {
        parent::__construct($db, $tableName, 'varchar');
        $this->maxLength = $maxLength;
    }

    public function validateValue(mixed $value): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        if (strlen((string)$value) > $this->maxLength) {
            return false;
        }

        return true;
    }

    public function transformForStorage(mixed $value): string
    {
        return (string)$value;
    }

    public function transformFromStorage(mixed $value): string
    {
        return (string)$value;
    }
}
