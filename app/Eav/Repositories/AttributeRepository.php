<?php
// app/Eav/Repositories/AttributeRepository.php
namespace Eav\Repositories;

use Core\Database\Database;
use Eav\Models\Attribute;
use Eav\Models\EntityType;
use Eav\Cache\CacheManager;

/**
 * Attribute Repository
 * 
 * Manages attribute definitions, validation rules, and schema caching
 */
class AttributeRepository
{
    private Database $db;
    private CacheManager $cache;
    private array $attributeCache = [];
    private array $entityTypeCache = [];

    public function __construct(Database $db, CacheManager $cache)
    {
        $this->db = $db;
        $this->cache = $cache;
    }

    /**
     * Get attribute by ID
     */
    public function findById(int $id): ?Attribute
    {
        if (isset($this->attributeCache[$id])) {
            return $this->attributeCache[$id];
        }

        $cacheKey = "attribute:{$id}";
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            $attribute = Attribute::newFromBuilder($cached);
            $this->attributeCache[$id] = $attribute;
            return $attribute;
        }

        $attribute = Attribute::find($id);
        if ($attribute) {
            $this->cache->set($cacheKey, $attribute->getData(), 7200);
            $this->attributeCache[$id] = $attribute;
        }

        return $attribute;
    }

    /**
     * Get attribute by code and entity type
     */
    public function findByCode(string $code, int $entityTypeId): ?Attribute
    {
        $cacheKey = "attribute:code:{$entityTypeId}:{$code}";
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return Attribute::newFromBuilder($cached);
        }

        $attribute = Attribute::where('attribute_code', $code)
            ->where('entity_type_id', $entityTypeId)
            ->first();

        if ($attribute) {
            $this->cache->set($cacheKey, $attribute->getData(), 7200);
        }

        return $attribute;
    }

    /**
     * Get all attributes for an entity type
     */
    public function getByEntityType(int $entityTypeId): array
    {
        $cacheKey = "attributes:entity_type:{$entityTypeId}";
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return array_map(fn($data) => Attribute::newFromBuilder($data), $cached);
        }

        $attributes = Attribute::where('entity_type_id', $entityTypeId)
            ->orderBy('sort_order', 'ASC')
            ->get();

        if (!empty($attributes)) {
            $data = array_map(fn($attr) => $attr->getData(), $attributes);
            $this->cache->set($cacheKey, $data, 7200);
        }

        return $attributes;
    }

    /**
     * Get searchable attributes for an entity type
     */
    public function getSearchableAttributes(int $entityTypeId): array
    {
        $cacheKey = "attributes:searchable:{$entityTypeId}";
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return array_map(fn($data) => Attribute::newFromBuilder($data), $cached);
        }

        $attributes = Attribute::where('entity_type_id', $entityTypeId)
            ->where('is_searchable', true)
            ->orderBy('sort_order', 'ASC')
            ->get();

        if (!empty($attributes)) {
            $data = array_map(fn($attr) => $attr->getData(), $attributes);
            $this->cache->set($cacheKey, $data, 7200);
        }

        return $attributes;
    }

    /**
     * Get filterable attributes for an entity type
     */
    public function getFilterableAttributes(int $entityTypeId): array
    {
        $cacheKey = "attributes:filterable:{$entityTypeId}";
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return array_map(fn($data) => Attribute::newFromBuilder($data), $cached);
        }

        $attributes = Attribute::where('entity_type_id', $entityTypeId)
            ->where('is_filterable', true)
            ->orderBy('sort_order', 'ASC')
            ->get();

        if (!empty($attributes)) {
            $data = array_map(fn($attr) => $attr->getData(), $attributes);
            $this->cache->set($cacheKey, $data, 7200);
        }

        return $attributes;
    }

    /**
     * Create a new attribute
     */
    public function create(array $data): Attribute
    {
        $attribute = new Attribute($data);
        $attribute->save();

        // Clear entity type attribute cache
        $this->invalidateEntityTypeCache($attribute->entity_type_id);

        return $attribute;
    }

    /**
     * Update an attribute
     */
    public function update(int $id, array $data): bool
    {
        $attribute = $this->findById($id);
        if (!$attribute) {
            return false;
        }

        $attribute->fill($data);
        $result = $attribute->save();

        if ($result) {
            $this->invalidateAttributeCache($id);
            $this->invalidateEntityTypeCache($attribute->entity_type_id);
        }

        return $result;
    }

    /**
     * Delete an attribute
     */
    public function delete(int $id): bool
    {
        $attribute = $this->findById($id);
        if (!$attribute) {
            return false;
        }

        $entityTypeId = $attribute->entity_type_id;
        $result = $attribute->delete();

        if ($result) {
            $this->invalidateAttributeCache($id);
            $this->invalidateEntityTypeCache($entityTypeId);
        }

        return $result;
    }

    /**
     * Get entity type by ID
     */
    public function getEntityType(int $id): ?EntityType
    {
        if (isset($this->entityTypeCache[$id])) {
            return $this->entityTypeCache[$id];
        }

        $cacheKey = "entity_type:{$id}";
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            $entityType = EntityType::newFromBuilder($cached);
            $this->entityTypeCache[$id] = $entityType;
            return $entityType;
        }

        $entityType = EntityType::find($id);
        if ($entityType) {
            $this->cache->set($cacheKey, $entityType->getData(), 7200);
            $this->entityTypeCache[$id] = $entityType;
        }

        return $entityType;
    }

    /**
     * Get entity type by code
     */
    public function getEntityTypeByCode(string $code): ?EntityType
    {
        $cacheKey = "entity_type:code:{$code}";
        $cached = $this->cache->get($cacheKey);

        if ($cached !== null) {
            return EntityType::newFromBuilder($cached);
        }

        $entityType = EntityType::where('entity_type_code', $code)->first();
        if ($entityType) {
            $this->cache->set($cacheKey, $entityType->getData(), 7200);
        }

        return $entityType;
    }

    /**
     * Get attributes by backend type
     */
    public function getByBackendType(int $entityTypeId, string $backendType): array
    {
        return Attribute::where('entity_type_id', $entityTypeId)
            ->where('backend_type', $backendType)
            ->orderBy('sort_order', 'ASC')
            ->get();
    }

    /**
     * Validate attribute value against rules
     */
    public function validateValue(Attribute $attribute, mixed $value): bool
    {
        // Check required
        if ($attribute->isRequired() && ($value === null || $value === '')) {
            return false;
        }

        // Get validation rules
        $rules = $attribute->getValidationRules();
        
        // Basic type validation
        switch ($attribute->backend_type) {
            case 'int':
                if (!is_numeric($value) || (int)$value != $value) {
                    return false;
                }
                break;
            case 'decimal':
                if (!is_numeric($value)) {
                    return false;
                }
                break;
            case 'datetime':
                if (is_string($value) && strtotime($value) === false) {
                    return false;
                }
                break;
        }

        // Apply custom validation rules
        foreach ($rules as $rule => $params) {
            if (!$this->applyValidationRule($value, $rule, $params)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Apply a validation rule
     */
    private function applyValidationRule(mixed $value, string $rule, mixed $params): bool
    {
        return match($rule) {
            'min' => is_numeric($value) && $value >= $params,
            'max' => is_numeric($value) && $value <= $params,
            'minLength' => is_string($value) && strlen($value) >= $params,
            'maxLength' => is_string($value) && strlen($value) <= $params,
            'pattern' => is_string($value) && preg_match($params, $value),
            'in' => is_array($params) && in_array($value, $params),
            default => true
        };
    }

    /**
     * Invalidate attribute cache
     */
    private function invalidateAttributeCache(int $id): void
    {
        $this->cache->delete("attribute:{$id}");
        unset($this->attributeCache[$id]);
    }

    /**
     * Invalidate entity type cache
     */
    private function invalidateEntityTypeCache(int $entityTypeId): void
    {
        $this->cache->delete("attributes:entity_type:{$entityTypeId}");
        $this->cache->delete("attributes:searchable:{$entityTypeId}");
        $this->cache->delete("attributes:filterable:{$entityTypeId}");
    }

    /**
     * Clear all caches
     */
    public function clearCache(): void
    {
        $this->attributeCache = [];
        $this->entityTypeCache = [];
    }
}
