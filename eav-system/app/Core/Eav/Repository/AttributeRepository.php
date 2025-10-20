<?php
// app/Core/Eav/Repository/AttributeRepository.php
namespace Core\Eav\Repository;

use Core\Database\Database;
use Core\Eav\Model\Attribute;
use Core\Di\Injectable;

/**
 * Repository for attribute metadata queries
 */
class AttributeRepository
{
    use Injectable;

    private Database $db;
    private array $cache = [];

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Find attribute by code and entity type
     */
    public function findByCode(string $entityTypeCode, string $attributeCode): ?Attribute
    {
        // Get entity type ID
        $entityType = $this->db->table('eav_entity_type')
            ->where('entity_code', $entityTypeCode)
            ->first();

        if (!$entityType) {
            return null;
        }

        $attributeData = $this->db->table('eav_attribute')
            ->where('entity_type_id', $entityType['entity_type_id'])
            ->where('attribute_code', $attributeCode)
            ->first();

        if (!$attributeData) {
            return null;
        }

        return $this->hydrateAttribute($attributeData);
    }

    /**
     * Find attribute by ID
     */
    public function findById(int $attributeId): ?Attribute
    {
        $attributeData = $this->db->table('eav_attribute')
            ->where('attribute_id', $attributeId)
            ->first();

        if (!$attributeData) {
            return null;
        }

        return $this->hydrateAttribute($attributeData);
    }

    /**
     * Find all attributes for entity type
     */
    public function findByEntityType(string $entityTypeCode): array
    {
        $cacheKey = "entity_type:{$entityTypeCode}";
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        // Get entity type ID
        $entityType = $this->db->table('eav_entity_type')
            ->where('entity_code', $entityTypeCode)
            ->first();

        if (!$entityType) {
            return [];
        }

        $attributesData = $this->db->table('eav_attribute')
            ->where('entity_type_id', $entityType['entity_type_id'])
            ->orderBy('sort_order', 'ASC')
            ->get();

        $attributes = [];
        foreach ($attributesData as $data) {
            $attributes[] = $this->hydrateAttribute($data);
        }

        $this->cache[$cacheKey] = $attributes;
        return $attributes;
    }

    /**
     * Find searchable attributes for entity type
     */
    public function findSearchable(string $entityTypeCode): array
    {
        $allAttributes = $this->findByEntityType($entityTypeCode);
        
        return array_filter($allAttributes, function($attr) {
            return $attr->isSearchable();
        });
    }

    /**
     * Find filterable attributes for entity type
     */
    public function findFilterable(string $entityTypeCode): array
    {
        $allAttributes = $this->findByEntityType($entityTypeCode);
        
        return array_filter($allAttributes, function($attr) {
            return $attr->isFilterable();
        });
    }

    /**
     * Find required attributes for entity type
     */
    public function findRequired(string $entityTypeCode): array
    {
        $allAttributes = $this->findByEntityType($entityTypeCode);
        
        return array_filter($allAttributes, function($attr) {
            return $attr->isRequired();
        });
    }

    /**
     * Find attributes by backend type
     */
    public function findByBackendType(string $entityTypeCode, string $backendType): array
    {
        $allAttributes = $this->findByEntityType($entityTypeCode);
        
        return array_filter($allAttributes, function($attr) use ($backendType) {
            return $attr->getBackendType() === $backendType;
        });
    }

    /**
     * Get attribute ID by code
     */
    public function getAttributeId(string $entityTypeCode, string $attributeCode): ?int
    {
        $attribute = $this->findByCode($entityTypeCode, $attributeCode);
        return $attribute ? $attribute->getAttributeId() : null;
    }

    /**
     * Clear cache
     */
    public function clearCache(string $entityTypeCode = null): void
    {
        if ($entityTypeCode) {
            $cacheKey = "entity_type:{$entityTypeCode}";
            unset($this->cache[$cacheKey]);
        } else {
            $this->cache = [];
        }
    }

    /**
     * Hydrate Attribute model from database row
     */
    private function hydrateAttribute(array $data): Attribute
    {
        return new Attribute([
            'code' => $data['attribute_code'],
            'label' => $data['attribute_label'],
            'backend_type' => $data['backend_type'],
            'frontend_type' => $data['frontend_type'],
            'is_required' => (bool) $data['is_required'],
            'is_unique' => (bool) $data['is_unique'],
            'is_searchable' => (bool) $data['is_searchable'],
            'is_filterable' => (bool) $data['is_filterable'],
            'default_value' => $data['default_value'] ? json_decode($data['default_value'], true) : null,
            'validation_rules' => $data['validation_rules'] ? json_decode($data['validation_rules'], true) : [],
            'sort_order' => (int) $data['sort_order'],
            'attribute_id' => (int) $data['attribute_id']
        ]);
    }
}
