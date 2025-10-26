<?php

namespace Core;

/**
 * PSR-4 Compliant Autoloader
 * 
 * Automatically loads classes following PSR-4 naming conventions
 * Maps namespaces to directory structures
 */
class Autoloader
{
    /**
     * Registered namespace prefixes and their base directories
     * 
     * @var array
     */
    protected static array $prefixes = [];

    /**
     * Register the autoloader with SPL
     * 
     * @return void
     */
    public static function register(): void
    {
        spl_autoload_register([self::class, 'loadClass']);
    }

    /**
     * Add a namespace prefix and base directory
     * 
     * @param string $prefix The namespace prefix
     * @param string $baseDir The base directory for the namespace
     * @param bool $prepend Prepend to the stack instead of append
     * @return void
     */
    public static function addNamespace(string $prefix, string $baseDir, bool $prepend = false): void
    {
        // Normalize namespace prefix
        $prefix = trim($prefix, '\\') . '\\';
        
        // Normalize base directory with trailing separator
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Initialize the namespace prefix array if needed
        if (!isset(self::$prefixes[$prefix])) {
            self::$prefixes[$prefix] = [];
        }

        // Add base directory for the namespace prefix
        if ($prepend) {
            array_unshift(self::$prefixes[$prefix], $baseDir);
        } else {
            self::$prefixes[$prefix][] = $baseDir;
        }
    }

    /**
     * Load the class file for a given class name
     * 
     * @param string $class The fully-qualified class name
     * @return bool True if file was loaded, false otherwise
     */
    protected static function loadClass(string $class): bool
    {
        // Current namespace prefix
        $prefix = $class;

        // Work backwards through namespace names to find mapped file
        while (false !== $pos = strrpos($prefix, '\\')) {
            // Retain trailing namespace separator
            $prefix = substr($class, 0, $pos + 1);
            
            // Rest is the relative class name
            $relativeClass = substr($class, $pos + 1);

            // Try to load mapped file for prefix and relative class
            $mapped = self::loadMappedFile($prefix, $relativeClass);
            if ($mapped) {
                return true;
            }

            // Remove trailing separator for next iteration
            $prefix = rtrim($prefix, '\\');
        }

        return false;
    }

    /**
     * Load mapped file for a namespace prefix and relative class
     * 
     * @param string $prefix The namespace prefix
     * @param string $relativeClass The relative class name
     * @return bool True if file exists and was loaded, false otherwise
     */
    protected static function loadMappedFile(string $prefix, string $relativeClass): bool
    {
        // No base directories for this namespace prefix
        if (!isset(self::$prefixes[$prefix])) {
            return false;
        }

        // Look through base directories for this namespace prefix
        foreach (self::$prefixes[$prefix] as $baseDir) {
            // Replace namespace separators with directory separators
            // and append with .php
            $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

            // If the mapped file exists, require it
            if (self::requireFile($file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Require a file if it exists
     * 
     * @param string $file The file to require
     * @return bool True if file exists, false otherwise
     */
    protected static function requireFile(string $file): bool
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }
        return false;
    }

    /**
     * Get all registered namespaces
     * 
     * @return array
     */
    public static function getNamespaces(): array
    {
        return self::$prefixes;
    }
}
