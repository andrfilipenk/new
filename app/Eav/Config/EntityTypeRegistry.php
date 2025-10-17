<?php
// app/Eav/Config/EntityTypeRegistry.php
namespace Eav\Config;

use Eav\Model\EntityType;
use Eav\Exception\ConfigurationException;
use Eav\Exception\EntityException;

/**
 * Entity Type Registry
 * 
 * Maintains a runtime index of all configured entity types.
 */
class EntityTypeRegistry
{
    /**
     * Configuration loader
     */
    protected ConfigLoader $configLoader;

    /**
     * Entity types indexed by code
     */
    protected array $entityTypes = [];

    /**
     * Entity types indexed by ID
     */
    protected array $entityTypesById = [];

    /**
     * Is registry loaded?
     */
    protected bool $loaded = false;

    /**
     * Constructor
     */
    public function __construct(ConfigLoader $configLoader)
    {
        $this->configLoader = $configLoader;
    }

    /**
     * Load all entity types from configuration
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->entityTypes = $this->configLoader->loadAll();
        $this->loaded = true;

        // Build ID index if IDs are set
        $this->rebuildIdIndex();
    }

    /**
     * Get entity type by code
     */
    public function getByCode(string $code): EntityType
    {
        $this->load();

        if (!isset($this->entityTypes[$code])) {
            throw EntityException::invalidType($code);
        }

        return $this->entityTypes[$code];
    }

    /**
     * Get entity type by ID
     */
    public function getById(int $id): EntityType
    {
        $this->load();

        if (!isset($this->entityTypesById[$id])) {
            throw new EntityException("Entity type with ID {$id} not found");
        }

        return $this->entityTypesById[$id];
    }

    /**
     * Check if entity type exists by code
     */
    public function has(string $code): bool
    {
        $this->load();
        return isset($this->entityTypes[$code]);
    }

    /**
     * Get all entity types
     */
    public function getAll(): array
    {
        $this->load();
        return $this->entityTypes;
    }

    /**
     * Get all entity type codes
     */
    public function getCodes(): array
    {
        $this->load();
        return array_keys($this->entityTypes);
    }

    /**
     * Register an entity type
     */
    public function register(EntityType $entityType): void
    {
        $code = $entityType->getEntityCode();
        $this->entityTypes[$code] = $entityType;

        if ($entityType->getEntityTypeId()) {
            $this->entityTypesById[$entityType->getEntityTypeId()] = $entityType;
        }

        $this->loaded = true;
    }

    /**
     * Rebuild ID index
     */
    protected function rebuildIdIndex(): void
    {
        $this->entityTypesById = [];
        
        foreach ($this->entityTypes as $entityType) {
            if ($entityType->getEntityTypeId()) {
                $this->entityTypesById[$entityType->getEntityTypeId()] = $entityType;
            }
        }
    }

    /**
     * Reload all entity types
     */
    public function reload(): void
    {
        $this->configLoader->reload();
        $this->entityTypes = [];
        $this->entityTypesById = [];
        $this->loaded = false;
        $this->load();
    }

    /**
     * Clear registry
     */
    public function clear(): void
    {
        $this->entityTypes = [];
        $this->entityTypesById = [];
        $this->loaded = false;
    }
}
