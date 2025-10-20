<?php
// app/Core/Eav/Config/ConfigLoader.php
namespace Core\Eav\Config;

use Core\Eav\Model\EntityType;
use Core\Eav\Exception\ConfigurationException;

/**
 * Loads entity type configurations from files
 */
class ConfigLoader
{
    private string $configPath;

    public function __construct(string $configPath = null)
    {
        $this->configPath = $configPath ?? __DIR__ . '/../../../../config/eav';
    }

    /**
     * Load entity type configuration by code
     */
    public function load(string $entityTypeCode): EntityType
    {
        $configFile = $this->configPath . '/' . $entityTypeCode . '.php';
        
        if (!file_exists($configFile)) {
            throw new ConfigurationException(
                "Entity type configuration not found: {$entityTypeCode}",
                "Configuration file missing",
                ['file' => $configFile]
            );
        }

        $config = require $configFile;
        
        if (!is_array($config)) {
            throw new ConfigurationException(
                "Invalid configuration format for entity type: {$entityTypeCode}",
                "Configuration must return an array",
                ['file' => $configFile]
            );
        }

        $config['code'] = $entityTypeCode;
        
        return new EntityType($config);
    }

    /**
     * Load all entity type configurations
     */
    public function loadAll(): array
    {
        if (!is_dir($this->configPath)) {
            return [];
        }

        $entityTypes = [];
        $files = glob($this->configPath . '/*.php');
        
        foreach ($files as $file) {
            $code = basename($file, '.php');
            $entityTypes[$code] = $this->load($code);
        }

        return $entityTypes;
    }

    /**
     * Check if configuration exists
     */
    public function exists(string $entityTypeCode): bool
    {
        $configFile = $this->configPath . '/' . $entityTypeCode . '.php';
        return file_exists($configFile);
    }
}
