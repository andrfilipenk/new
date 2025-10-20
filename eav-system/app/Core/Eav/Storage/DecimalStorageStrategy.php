<?php
// app/Eav/Storage/DecimalStorageStrategy.php
namespace Eav\Storage;

/**
 * Decimal Storage Strategy
 * 
 * Handles storage of decimal/float values
 */
class DecimalStorageStrategy extends AbstractStorageStrategy
{
    private int $precision;
    private int $scale;

    public function __construct($db, string $tableName, int $precision = 12, int $scale = 4)
    {
        parent::__construct($db, $tableName, 'decimal');
        $this->precision = $precision;
        $this->scale = $scale;
    }

    public function validateValue(mixed $value): bool
    {
        return is_numeric($value);
    }

    public function transformForStorage(mixed $value): float
    {
        return round((float)$value, $this->scale);
    }

    public function transformFromStorage(mixed $value): float
    {
        return (float)$value;
    }
}
