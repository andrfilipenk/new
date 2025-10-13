<?php
/**
 * FieldFactory Class
 * 
 * Factory for creating field instances based on type and configuration.
 * Provides a centralized way to instantiate fields with type mapping.
 * 
 * @package Core\Forms\Fields
 * @since 2.0.0
 */

namespace Core\Forms\Fields;

use InvalidArgumentException;

class FieldFactory
{
    /**
     * @var array Field type to class mapping
     */
    private static array $typeMap = [
        'text' => InputField::class,
        'email' => InputField::class,
        'password' => InputField::class,
        'number' => InputField::class,
        'tel' => InputField::class,
        'url' => InputField::class,
        'date' => InputField::class,
        'time' => InputField::class,
        'datetime-local' => InputField::class,
        'hidden' => InputField::class,
        'search' => InputField::class,
        'color' => InputField::class,
        'range' => InputField::class,
        'select' => SelectField::class,
        'textarea' => TextAreaField::class,
    ];

    /**
     * @var array Custom field type registrations
     */
    private static array $customTypes = [];

    /**
     * Create a field instance
     * 
     * @param string $name Field name
     * @param string $type Field type
     * @param array $config Field configuration
     * @return FieldInterface
     * @throws InvalidArgumentException If type is not registered
     */
    public static function create(string $name, string $type, array $config = []): FieldInterface
    {
        // Merge type into config
        $config['type'] = $type;
        
        // Check custom types first
        if (isset(self::$customTypes[$type])) {
            $className = self::$customTypes[$type];
            return new $className($name, $config);
        }
        
        // Check built-in types
        if (isset(self::$typeMap[$type])) {
            $className = self::$typeMap[$type];
            return new $className($name, $config);
        }
        
        throw new InvalidArgumentException("Unknown field type: {$type}");
    }

    /**
     * Register a custom field type
     * 
     * @param string $type Field type identifier
     * @param string $className Fully qualified class name
     * @return void
     * @throws InvalidArgumentException If class doesn't implement FieldInterface
     */
    public static function registerType(string $type, string $className): void
    {
        if (!is_subclass_of($className, FieldInterface::class)) {
            throw new InvalidArgumentException(
                "Class {$className} must implement " . FieldInterface::class
            );
        }
        
        self::$customTypes[$type] = $className;
    }

    /**
     * Check if a field type is registered
     * 
     * @param string $type Field type
     * @return bool
     */
    public static function hasType(string $type): bool
    {
        return isset(self::$typeMap[$type]) || isset(self::$customTypes[$type]);
    }

    /**
     * Get all registered field types
     * 
     * @return array
     */
    public static function getRegisteredTypes(): array
    {
        return array_merge(array_keys(self::$typeMap), array_keys(self::$customTypes));
    }

    /**
     * Create multiple fields from configuration array
     * 
     * @param array $fieldsConfig Fields configuration
     * @return array<FieldInterface>
     */
    public static function createMultiple(array $fieldsConfig): array
    {
        $fields = [];
        
        foreach ($fieldsConfig as $name => $config) {
            $type = $config['type'] ?? 'text';
            $fields[$name] = self::create($name, $type, $config);
        }
        
        return $fields;
    }

    /**
     * Quick factory methods for common field types
     */

    public static function text(string $name, array $config = []): InputField
    {
        return InputField::text($name, $config);
    }

    public static function email(string $name, array $config = []): InputField
    {
        return InputField::email($name, $config);
    }

    public static function password(string $name, array $config = []): InputField
    {
        return InputField::password($name, $config);
    }

    public static function number(string $name, array $config = []): InputField
    {
        return InputField::number($name, $config);
    }

    public static function hidden(string $name, mixed $value = null): InputField
    {
        return InputField::hidden($name, $value);
    }

    public static function select(string $name, array $options = [], array $config = []): SelectField
    {
        return SelectField::make($name, $options, $config);
    }

    public static function textarea(string $name, array $config = []): TextAreaField
    {
        return TextAreaField::make($name, $config);
    }
}
