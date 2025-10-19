<?php
// app/Eav/Storage/IntStorageStrategy.php
namespace Eav\Storage;

/**
 * Integer Storage Strategy
 * 
 * Handles storage of integer values
 */
class IntStorageStrategy extends AbstractStorageStrategy
{
    public function __construct($db, string $tableName)
    {
        parent::__construct($db, $tableName, 'int');
    }

    public function validateValue(mixed $value): bool
    {
        return is_numeric($value) && (int)$value == $value;
    }

    public function transformForStorage(mixed $value): int
    {
        return (int)$value;
    }

    public function transformFromStorage(mixed $value): int
    {
        return (int)$value;
    }
}
