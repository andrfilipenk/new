<?php
// app/Eav/Exception/ConfigurationException.php
namespace Eav\Exception;

/**
 * Configuration Exception
 * 
 * Thrown when there are errors in EAV configuration files or settings.
 */
class ConfigurationException extends EavException
{
    /**
     * Invalid configuration keys
     */
    protected array $invalidKeys = [];

    /**
     * Set invalid configuration keys
     */
    public function setInvalidKeys(array $keys): self
    {
        $this->invalidKeys = $keys;
        return $this;
    }

    /**
     * Get invalid configuration keys
     */
    public function getInvalidKeys(): array
    {
        return $this->invalidKeys;
    }

    /**
     * Create exception for missing required configuration
     */
    public static function missingRequired(string $key): self
    {
        return (new self("Required configuration key '{$key}' is missing"))
            ->setInvalidKeys([$key]);
    }

    /**
     * Create exception for invalid configuration value
     */
    public static function invalidValue(string $key, $value, string $expected): self
    {
        return (new self(
            "Invalid value for configuration key '{$key}': expected {$expected}, got " . gettype($value)
        ))->setInvalidKeys([$key]);
    }

    /**
     * Create exception for invalid entity configuration
     */
    public static function invalidEntityConfig(string $entityCode, array $errors): self
    {
        return (new self("Invalid entity configuration for '{$entityCode}'"))
            ->setContext(['entity' => $entityCode, 'errors' => $errors]);
    }

    /**
     * Create exception for invalid attribute configuration
     */
    public static function invalidAttributeConfig(string $attributeCode, array $errors): self
    {
        return (new self("Invalid attribute configuration for '{$attributeCode}'"))
            ->setContext(['attribute' => $attributeCode, 'errors' => $errors]);
    }
}
