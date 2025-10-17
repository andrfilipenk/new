<?php
// app/Core/Eav/Config/EntityTypeRegistry.php
namespace Core\Eav\Config;

use Core\Eav\Model\EntityType;
use Core\Eav\Exception\ConfigurationException;

/**
 * Runtime index/cache of entity types
 */
class EntityTypeRegistry
{
    private array $entityTypes = [];
    private ConfigLoader $configLoader;
    private bool $initialized = false;

    public function __construct(ConfigLoader $configLoader)
    {
        $this->configLoader = $configLoader;
    }

    /**
     * Initialize registry by loading all configurations
     */
    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->entityTypes = $this->configLoader->loadAll();
        $this->initialized = true;
    }

    /**
     * Get entity type by code
     */
    public function get(string $code): EntityType
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        if (!isset($this->entityTypes[$code])) {
            // Try to load it on-demand
            if ($this->configLoader->exists($code)) {
                $this->entityTypes[$code] = $this->configLoader->load($code);
            } else {
                throw new ConfigurationException(
                    "Entity type not found: {$code}",
                    "Entity type not registered",
                    ['code' => $code]
                );
            }
        }

        return $this->entityTypes[$code];
    }

    /**
     * Check if entity type exists
     */
    public function has(string $code): bool
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return isset($this->entityTypes[$code]) || $this->configLoader->exists($code);
    }

    /**
     * Register an entity type programmatically
     */
    public function register(EntityType $entityType): void
    {
        $this->entityTypes[$entityType->getCode()] = $entityType;
    }

    /**
     * Get all registered entity types
     */
    public function all(): array
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return $this->entityTypes;
    }
}
