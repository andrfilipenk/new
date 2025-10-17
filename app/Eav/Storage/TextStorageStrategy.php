<?php
// app/Eav/Storage/TextStorageStrategy.php
namespace Eav\Storage;

/**
 * Text Storage Strategy
 * 
 * Handles storage of text/long text values
 */
class TextStorageStrategy extends AbstractStorageStrategy
{
    public function __construct($db, string $tableName)
    {
        parent::__construct($db, $tableName, 'text');
    }

    public function validateValue(mixed $value): bool
    {
        return is_string($value);
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
