<?php
// app/Eav/Config/ConfigLoader.php
namespace Eav\Config;

use Eav\Model\EntityType;
use Eav\Exception\ConfigurationException;

/**
 * Configuration Loader
 * 
 * Loads and parses entity configuration files.
 */
class ConfigLoader
{
    /**
     * Configuration directory path
     */
    protected string $configPath;

    /**
     * Loaded configurations cache
     */
    protected array $loadedConfigs = [];

    /**
     * Constructor
     */
    public function __construct(string $configPath)
    {
        $this->configPath = rtrim($configPath, '/');
        
        if (!is_dir($this->configPath)) {
            throw new ConfigurationException(
                "Configuration directory does not exist: {$this->configPath}"
            );
        }
    }

    /**
     * Load all entity configurations
     */
    public function loadAll(): array
    {
        $entityTypes = [];
        $configFiles = glob($this->configPath . '/*.php');

        foreach ($configFiles as $file) {
            $entityCode = basename($file, '.php');
            try {
                $entityType = $this->load($entityCode);
                $entityTypes[$entityCode] = $entityType;
            } catch (\Exception $e) {
                throw new ConfigurationException(
                    "Failed to load entity configuration '{$entityCode}': " . $e->getMessage(),
                    0,
                    $e
                );
            }
        }

        return $entityTypes;
    }

    /**
     * Load a specific entity configuration
     */
    public function load(string $entityCode): EntityType
    {
        // Check cache first
        if (isset($this->loadedConfigs[$entityCode])) {
            return $this->loadedConfigs[$entityCode];
        }

        $configFile = $this->configPath . '/' . $entityCode . '.php';
        
        if (!file_exists($configFile)) {
            throw new ConfigurationException(
                "Entity configuration file not found: {$configFile}"
            );
        }

        // Load configuration array
        $config = require $configFile;

        if (!is_array($config)) {
            throw new ConfigurationException(
                "Entity configuration file must return an array: {$configFile}"
            );
        }

        // Ensure entity_code is set
        if (!isset($config['entity_code'])) {
            $config['entity_code'] = $entityCode;
        }

        // Validate configuration
        $this->validateConfig($config);

        // Create EntityType instance
        $entityType = new EntityType($config);

        // Cache the loaded configuration
        $this->loadedConfigs[$entityCode] = $entityType;

        return $entityType;
    }

    /**
     * Validate entity configuration
     */
    protected function validateConfig(array $config): void
    {
        $errors = [];

        // Required fields
        $requiredFields = ['entity_code', 'entity_label'];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        // Validate storage strategy if provided
        if (isset($config['storage_strategy'])) {
            if (!in_array($config['storage_strategy'], ['eav', 'flat'])) {
                $errors[] = "Invalid storage_strategy: must be 'eav' or 'flat'";
            }
        }

        // Validate attributes if provided
        if (isset($config['attributes'])) {
            if (!is_array($config['attributes'])) {
                $errors[] = "Attributes must be an array";
            } else {
                foreach ($config['attributes'] as $index => $attrConfig) {
                    $attrErrors = $this->validateAttributeConfig($attrConfig, $index);
                    $errors = array_merge($errors, $attrErrors);
                }
            }
        }

        if (!empty($errors)) {
            throw ConfigurationException::invalidEntityConfig(
                $config['entity_code'] ?? 'unknown',
                $errors
            );
        }
    }

    /**
     * Validate attribute configuration
     */
    protected function validateAttributeConfig(array $config, int $index): array
    {
        $errors = [];

        // Required fields
        $requiredFields = ['attribute_code', 'backend_type', 'frontend_type'];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || $config[$field] === '') {
                $errors[] = "Attribute #{$index}: Missing required field '{$field}'";
            }
        }

        // Validate backend_type
        if (isset($config['backend_type'])) {
            $validBackendTypes = ['varchar', 'int', 'decimal', 'datetime', 'text'];
            if (!in_array($config['backend_type'], $validBackendTypes)) {
                $errors[] = "Attribute #{$index}: Invalid backend_type '{$config['backend_type']}'";
            }
        }

        // Validate frontend_type
        if (isset($config['frontend_type'])) {
            $validFrontendTypes = ['text', 'textarea', 'select', 'multiselect', 'date', 'datetime', 'boolean', 'number'];
            if (!in_array($config['frontend_type'], $validFrontendTypes)) {
                $errors[] = "Attribute #{$index}: Invalid frontend_type '{$config['frontend_type']}'";
            }
        }

        // Validate boolean fields
        $booleanFields = ['is_required', 'is_unique', 'is_searchable', 'is_filterable', 'is_comparable'];
        foreach ($booleanFields as $field) {
            if (isset($config[$field]) && !is_bool($config[$field])) {
                $errors[] = "Attribute #{$index}: Field '{$field}' must be boolean";
            }
        }

        return $errors;
    }

    /**
     * Reload all configurations (clear cache)
     */
    public function reload(): void
    {
        $this->loadedConfigs = [];
    }

    /**
     * Check if configuration has been modified
     */
    public function hasChanged(string $entityCode): bool
    {
        $configFile = $this->configPath . '/' . $entityCode . '.php';
        
        if (!file_exists($configFile)) {
            return true; // File removed
        }

        if (!isset($this->loadedConfigs[$entityCode])) {
            return true; // Not loaded yet
        }

        // Check file modification time
        // In a real implementation, you might want to compare checksums or actual content
        return true; // For now, always assume changed
    }
}
