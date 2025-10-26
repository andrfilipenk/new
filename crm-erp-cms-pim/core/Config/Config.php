<?php

namespace Core\Config;

/**
 * Configuration Manager
 * 
 * Manages application configuration with environment-based loading,
 * dot-notation access, and caching support
 */
class Config
{
    /**
     * Configuration items
     * 
     * @var array
     */
    protected static array $items = [];

    /**
     * Configuration cache
     * 
     * @var array
     */
    protected static array $cache = [];

    /**
     * Configuration path
     * 
     * @var string
     */
    protected static string $configPath = '';

    /**
     * Environment variables
     * 
     * @var array
     */
    protected static array $env = [];

    /**
     * Initialize configuration
     * 
     * @param string $configPath
     * @return void
     */
    public static function initialize(string $configPath): void
    {
        self::$configPath = rtrim($configPath, DIRECTORY_SEPARATOR);
        self::loadEnvironment();
        self::loadConfigFiles();
    }

    /**
     * Load environment variables from .env file
     * 
     * @return void
     */
    protected static function loadEnvironment(): void
    {
        $envFile = dirname(self::$configPath) . DIRECTORY_SEPARATOR . '.env';
        
        if (!file_exists($envFile)) {
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes
                $value = trim($value, '"\'');
                
                self::$env[$key] = $value;
                
                // Also set in $_ENV and putenv
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }

    /**
     * Load all configuration files
     * 
     * @return void
     */
    protected static function loadConfigFiles(): void
    {
        if (!is_dir(self::$configPath)) {
            return;
        }

        $files = glob(self::$configPath . '/*.php');
        
        foreach ($files as $file) {
            $name = basename($file, '.php');
            self::$items[$name] = require $file;
        }
    }

    /**
     * Get configuration value using dot notation
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null): mixed
    {
        // Check cache first
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $value = self::getFromArray($key);
        
        if ($value !== null) {
            self::$cache[$key] = $value;
            return $value;
        }

        return $default;
    }

    /**
     * Get value from configuration array using dot notation
     * 
     * @param string $key
     * @return mixed
     */
    protected static function getFromArray(string $key): mixed
    {
        $keys = explode('.', $key);
        $value = self::$items;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Set configuration value
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $config = &self::$items;

        while (count($keys) > 1) {
            $segment = array_shift($keys);
            
            if (!isset($config[$segment]) || !is_array($config[$segment])) {
                $config[$segment] = [];
            }
            
            $config = &$config[$segment];
        }

        $config[array_shift($keys)] = $value;
        
        // Clear cache for this key
        unset(self::$cache[$key]);
    }

    /**
     * Check if configuration key exists
     * 
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return self::getFromArray($key) !== null;
    }

    /**
     * Get all configuration items
     * 
     * @return array
     */
    public static function all(): array
    {
        return self::$items;
    }

    /**
     * Get environment variable
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function env(string $key, $default = null): mixed
    {
        return self::$env[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Clear configuration cache
     * 
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * Reload configuration
     * 
     * @return void
     */
    public static function reload(): void
    {
        self::$items = [];
        self::$cache = [];
        self::loadEnvironment();
        self::loadConfigFiles();
    }

    /**
     * Get configuration for a specific file
     * 
     * @param string $file
     * @return array
     */
    public static function file(string $file): array
    {
        return self::$items[$file] ?? [];
    }
}
