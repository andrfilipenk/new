<?php
// app/Core/Eav/Repository/EntityRepository.php
namespace Core\Eav\Repository;

use Core\Database\Database;
use Core\Eav\Model\Entity;
use Core\Eav\Model\EntityType;
use Core\Eav\Manager\EntityManager;
use Core\Di\Injectable;

/**
 * Repository for entity queries with filtering and pagination
 */
class EntityRepository
{
    use Injectable;

    private Database $db;
    private EntityManager $entityManager;

    public function __construct(Database $db, EntityManager $entityManager)
    {
        $this->db = $db;
        $this->entityManager = $entityManager;
    }

    /**
     * Find entity by ID
     */
    public function findById(EntityType $entityType, int $id): ?Entity
    {
        return $this->entityManager->load($entityType, $id);
    }

    /**
     * Find entities by attribute value
     */
    public function findByAttribute(
        EntityType $entityType,
        string $attributeCode,
        mixed $value
    ): array {
        $attribute = $entityType->getAttribute($attributeCode);
        if (!$attribute) {
            return [];
        }

        $backendType = $attribute->getBackendType();
        $attributeId = $attribute->getAttributeId();

        if (!$attributeId) {
            return [];
        }

        // Query the appropriate value table
        $valueTable = "eav_value_{$backendType}";
        $results = $this->db->table($valueTable)
            ->where('attribute_id', $attributeId)
            ->where('value', $value)
            ->get();

        if (empty($results)) {
            return [];
        }

        $entityIds = array_column($results, 'entity_id');
        return $this->entityManager->loadMultiple($entityType, $entityIds);
    }

    /**
     * Find all entities of a type
     */
    public function findAll(EntityType $entityType, array $options = []): array
    {
        $query = $this->db->table($entityType->getEntityTable());

        // Apply limit
        if (isset($options['limit'])) {
            $query->limit($options['limit']);
        }

        // Apply offset
        if (isset($options['offset'])) {
            $query->offset($options['offset']);
        }

        // Apply order
        if (isset($options['order_by'])) {
            $direction = $options['order_direction'] ?? 'ASC';
            $query->orderBy($options['order_by'], $direction);
        }

        $records = $query->get();

        if (empty($records)) {
            return [];
        }

        $ids = array_column($records, 'entity_id');
        return $this->entityManager->loadMultiple($entityType, $ids);
    }

    /**
     * Count entities matching criteria
     */
    public function count(EntityType $entityType, array $criteria = []): int
    {
        $query = $this->db->table($entityType->getEntityTable());

        // Apply criteria (basic implementation)
        foreach ($criteria as $field => $value) {
            $query->where($field, $value);
        }

        return $query->count();
    }

    /**
     * Paginate entities
     */
    public function paginate(
        EntityType $entityType,
        array $criteria = [],
        int $perPage = 20,
        int $page = 1
    ): array {
        $offset = ($page - 1) * $perPage;

        $entities = $this->findAll($entityType, [
            'limit' => $perPage,
            'offset' => $offset
        ]);

        $total = $this->count($entityType, $criteria);

        return [
            'data' => $entities,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Find entities by multiple attribute values
     */
    public function findByAttributes(EntityType $entityType, array $attributeFilters): array
    {
        if (empty($attributeFilters)) {
            return $this->findAll($entityType);
        }

        // For complex queries, we need to join value tables
        // This is a simplified implementation - full implementation would optimize joins
        $entityIds = null;

        foreach ($attributeFilters as $attributeCode => $value) {
            $attribute = $entityType->getAttribute($attributeCode);
            if (!$attribute || !$attribute->getAttributeId()) {
                continue;
            }

            $backendType = $attribute->getBackendType();
            $valueTable = "eav_value_{$backendType}";

            $results = $this->db->table($valueTable)
                ->where('attribute_id', $attribute->getAttributeId())
                ->where('value', $value)
                ->get();

            $currentIds = array_column($results, 'entity_id');

            if ($entityIds === null) {
                $entityIds = $currentIds;
            } else {
                // Intersect - only entities matching all criteria
                $entityIds = array_intersect($entityIds, $currentIds);
            }

            if (empty($entityIds)) {
                return [];
            }
        }

        if ($entityIds === null || empty($entityIds)) {
            return [];
        }

        return $this->entityManager->loadMultiple($entityType, $entityIds);
    }

    /**
     * Search entities by searchable attributes
     */
    public function search(EntityType $entityType, string $searchTerm): array
    {
        $searchableAttributes = [];
        foreach ($entityType->getAttributes() as $attribute) {
            if ($attribute->isSearchable() && $attribute->getAttributeId()) {
                $searchableAttributes[] = $attribute;
            }
        }

        if (empty($searchableAttributes)) {
            return [];
        }

        $entityIds = [];

        foreach ($searchableAttributes as $attribute) {
            $backendType = $attribute->getBackendType();
            if ($backendType === 'text' || $backendType === 'varchar') {
                $valueTable = "eav_value_{$backendType}";
                
                $results = $this->db->table($valueTable)
                    ->where('attribute_id', $attribute->getAttributeId())
                    ->whereRaw('value LIKE ?', ["%{$searchTerm}%"])
                    ->get();

                $ids = array_column($results, 'entity_id');
                $entityIds = array_merge($entityIds, $ids);
            }
        }

        if (empty($entityIds)) {
            return [];
        }

        // Remove duplicates
        $entityIds = array_unique($entityIds);

        return $this->entityManager->loadMultiple($entityType, $entityIds);
    }
}
