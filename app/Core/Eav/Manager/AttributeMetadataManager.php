<?php
// app/Core/Eav/Manager/AttributeMetadataManager.php
namespace Core\Eav\Manager;

use Core\Database\Database;
use Core\Eav\Model\EntityType;
use Core\Eav\Model\Attribute;
use Core\Eav\Exception\ConfigurationException;
use Core\Di\Injectable;

/**
 * Manages attribute metadata in database
 */
class AttributeMetadataManager
{
    use Injectable;

    private Database $db;
    private array $cache = [];

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Synchronize attributes with database
     */
    public function syncAttributes(EntityType $entityType): bool
    {
        $entityTypeId = $entityType->getEntityTypeId();
        
        if (!$entityTypeId) {
            throw new ConfigurationException(
                "Entity type must have an ID before syncing attributes",
                "Invalid entity type",
                ['entity_type' => $entityType->getCode()]
            );
        }

        // Get existing attributes
        $existing = $this->loadAttributes($entityTypeId);
        $existingMap = [];
        foreach ($existing as $attr) {
            $existingMap[$attr['attribute_code']] = $attr;
        }

        // Sync each configured attribute
        foreach ($entityType->getAttributes() as $attribute) {
            $code = $attribute->getCode();
            
            if (!isset($existingMap[$code])) {
                // Create new attribute
                $attributeId = $this->createAttribute($entityTypeId, $attribute);
                $attribute->setAttributeId($attributeId);
            } else {
                // Update if changed
                $attributeId = (int) $existingMap[$code]['attribute_id'];
                $attribute->setAttributeId($attributeId);
                
                if ($this->hasChanged($attribute, $existingMap[$code])) {
                    $this->updateAttribute($attributeId, $attribute);
                }
            }
        }

        // Clear cache for this entity type
        unset($this->cache[$entityTypeId]);

        return true;
    }

    /**
     * Get attribute ID by entity type and attribute code
     */
    public function getAttributeId(int $entityTypeId, string $attributeCode): ?int
    {
        $attributes = $this->loadAttributes($entityTypeId);
        
        foreach ($attributes as $attr) {
            if ($attr['attribute_code'] === $attributeCode) {
                return (int) $attr['attribute_id'];
            }
        }

        return null;
    }

    /**
     * Load all attributes for entity type
     */
    public function loadAttributes(int $entityTypeId): array
    {
        if (isset($this->cache[$entityTypeId])) {
            return $this->cache[$entityTypeId];
        }

        $attributes = $this->db->table('eav_attribute')
            ->where('entity_type_id', $entityTypeId)
            ->orderBy('sort_order', 'ASC')
            ->get();

        $this->cache[$entityTypeId] = $attributes;

        return $attributes;
    }

    /**
     * Create new attribute
     */
    public function createAttribute(int $entityTypeId, Attribute $attribute): int
    {
        $data = array_merge(
            ['entity_type_id' => $entityTypeId],
            $attribute->toArray()
        );

        return (int) $this->db->table('eav_attribute')->insert($data);
    }

    /**
     * Update existing attribute
     */
    public function updateAttribute(int $attributeId, Attribute $attribute): bool
    {
        $this->db->table('eav_attribute')
            ->where('attribute_id', $attributeId)
            ->update($attribute->toArray());

        return true;
    }

    /**
     * Load attribute by ID
     */
    public function loadAttributeById(int $attributeId): ?array
    {
        return $this->db->table('eav_attribute')
            ->where('attribute_id', $attributeId)
            ->first();
    }

    /**
     * Check if attribute has changed
     */
    private function hasChanged(Attribute $attribute, array $existing): bool
    {
        $fields = [
            'attribute_label', 'backend_type', 'frontend_type',
            'is_required', 'is_unique', 'is_searchable', 'is_filterable',
            'default_value', 'validation_rules', 'sort_order'
        ];

        $attrArray = $attribute->toArray();

        foreach ($fields as $field) {
            $newValue = $attrArray[$field] ?? null;
            $oldValue = $existing[$field] ?? null;
            
            if ($newValue != $oldValue) {
                return true;
            }
        }

        return false;
    }
}
