<?php
// app/Core/Eav/Model/EntityType.php
namespace Core\Eav\Model;

/**
 * Represents an entity type definition
 */
class EntityType
{
    private string $code;
    private string $label;
    private string $entityTable;
    private string $storageStrategy = 'eav';
    private AttributeCollection $attributes;
    private ?int $entityTypeId = null;

    public function __construct(array $data)
    {
        $this->code = $data['code'] ?? '';
        $this->label = $data['label'] ?? '';
        $this->entityTable = $data['entity_table'] ?? 'eav_entity_' . $this->code;
        $this->storageStrategy = $data['storage_strategy'] ?? 'eav';
        
        $attributes = [];
        if (isset($data['attributes']) && is_array($data['attributes'])) {
            foreach ($data['attributes'] as $attrData) {
                $attributes[] = new Attribute($attrData);
            }
        }
        $this->attributes = new AttributeCollection($attributes);
        $this->entityTypeId = $data['entity_type_id'] ?? null;
    }

    public function getCode(): string { return $this->code; }
    public function getLabel(): string { return $this->label; }
    public function getEntityTable(): string { return $this->entityTable; }
    public function getStorageStrategy(): string { return $this->storageStrategy; }
    public function getAttributes(): AttributeCollection { return $this->attributes; }
    public function getEntityTypeId(): ?int { return $this->entityTypeId; }
    
    public function setEntityTypeId(int $id): void { $this->entityTypeId = $id; }
    
    public function getAttribute(string $code): ?Attribute
    {
        return $this->attributes->get($code);
    }

    public function hasAttribute(string $code): bool
    {
        return $this->attributes->has($code);
    }

    /**
     * Convert to array format for database storage
     */
    public function toArray(): array
    {
        return [
            'entity_code' => $this->code,
            'entity_label' => $this->label,
            'entity_table' => $this->entityTable,
            'storage_strategy' => $this->storageStrategy,
        ];
    }
}
