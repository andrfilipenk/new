<?php
// app/Eav/Model/EntityType.php
namespace Eav\Model;

use Eav\Exception\ConfigurationException;

/**
 * Entity Type Model
 * 
 * Represents an entity type in the EAV system.
 */
class EntityType
{
    /**
     * Entity type ID (from database)
     */
    protected ?int $entityTypeId = null;

    /**
     * Entity code (unique identifier)
     */
    protected string $entityCode;

    /**
     * Human-readable entity label
     */
    protected string $entityLabel;

    /**
     * Entity table name
     */
    protected string $entityTable;

    /**
     * Storage strategy ('eav' or 'flat')
     */
    protected string $storageStrategy = 'eav';

    /**
     * Enable cache for this entity type
     */
    protected bool $enableCache = true;

    /**
     * Cache TTL in seconds
     */
    protected int $cacheTtl = 3600;

    /**
     * Attribute collection
     */
    protected AttributeCollection $attributes;

    /**
     * Additional configuration
     */
    protected array $additionalConfig = [];

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        if (!isset($config['entity_code'])) {
            throw ConfigurationException::missingRequired('entity_code');
        }
        if (!isset($config['entity_label'])) {
            throw ConfigurationException::missingRequired('entity_label');
        }

        $this->entityCode = $config['entity_code'];
        $this->entityLabel = $config['entity_label'];
        
        // Set entity table name
        if (isset($config['entity_table'])) {
            $this->entityTable = $config['entity_table'];
        } else {
            $this->entityTable = $this->entityCode . '_entity';
        }

        // Set storage strategy
        if (isset($config['storage_strategy'])) {
            $this->setStorageStrategy($config['storage_strategy']);
        }

        // Set cache configuration
        $this->enableCache = $config['enable_cache'] ?? true;
        $this->cacheTtl = $config['cache_ttl'] ?? 3600;

        // Initialize attribute collection
        $this->attributes = new AttributeCollection();

        // Load attributes if provided
        if (isset($config['attributes']) && is_array($config['attributes'])) {
            foreach ($config['attributes'] as $attributeConfig) {
                $attributeConfig['entity_type_id'] = $this->entityTypeId ?? 0;
                $attribute = new Attribute($attributeConfig);
                $this->attributes->add($attribute);
            }
        }

        // Store additional configuration
        $this->additionalConfig = $config['additional'] ?? [];
    }

    /**
     * Get entity type ID
     */
    public function getEntityTypeId(): ?int
    {
        return $this->entityTypeId;
    }

    /**
     * Set entity type ID
     */
    public function setEntityTypeId(int $id): self
    {
        $this->entityTypeId = $id;
        
        // Update all attributes with this entity type ID
        foreach ($this->attributes as $attribute) {
            $attribute->setAttributeId($id);
        }
        
        return $this;
    }

    /**
     * Get entity code
     */
    public function getEntityCode(): string
    {
        return $this->entityCode;
    }

    /**
     * Get entity label
     */
    public function getEntityLabel(): string
    {
        return $this->entityLabel;
    }

    /**
     * Get entity table name
     */
    public function getEntityTable(): string
    {
        return $this->entityTable;
    }

    /**
     * Get storage strategy
     */
    public function getStorageStrategy(): string
    {
        return $this->storageStrategy;
    }

    /**
     * Set storage strategy with validation
     */
    public function setStorageStrategy(string $strategy): self
    {
        if (!in_array($strategy, ['eav', 'flat'])) {
            throw ConfigurationException::invalidValue(
                'storage_strategy',
                $strategy,
                'eav, flat'
            );
        }
        $this->storageStrategy = $strategy;
        return $this;
    }

    /**
     * Is cache enabled for this entity type?
     */
    public function isCacheEnabled(): bool
    {
        return $this->enableCache;
    }

    /**
     * Get cache TTL
     */
    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    /**
     * Get attributes collection
     */
    public function getAttributes(): AttributeCollection
    {
        return $this->attributes;
    }

    /**
     * Get a specific attribute by code
     */
    public function getAttribute(string $code): ?Attribute
    {
        return $this->attributes->get($code);
    }

    /**
     * Add an attribute
     */
    public function addAttribute(Attribute $attribute): self
    {
        $this->attributes->add($attribute);
        return $this;
    }

    /**
     * Check if attribute exists
     */
    public function hasAttribute(string $code): bool
    {
        return $this->attributes->has($code);
    }

    /**
     * Get additional configuration value
     */
    public function getConfigValue(string $key, $default = null): mixed
    {
        return $this->additionalConfig[$key] ?? $default;
    }

    /**
     * Set additional configuration value
     */
    public function setConfigValue(string $key, $value): self
    {
        $this->additionalConfig[$key] = $value;
        return $this;
    }

    /**
     * Convert entity type to array
     */
    public function toArray(): array
    {
        return [
            'entity_type_id' => $this->entityTypeId,
            'entity_code' => $this->entityCode,
            'entity_label' => $this->entityLabel,
            'entity_table' => $this->entityTable,
            'storage_strategy' => $this->storageStrategy,
            'enable_cache' => $this->enableCache,
            'cache_ttl' => $this->cacheTtl,
            'attributes' => $this->attributes->toArray(),
            'additional' => $this->additionalConfig,
        ];
    }
}
