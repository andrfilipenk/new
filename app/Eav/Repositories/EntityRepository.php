<?php
// app/Eav/Repositories/EntityRepository.php
namespace Eav\Repositories;

use Eav\Models\Entity;
use Eav\Services\EntityManager;
use Eav\Query\QueryFactory;
use Eav\Query\EavQueryBuilder;
use Eav\Cache\QueryCache;

/**
 * Entity Repository
 * 
 * High-level repository pattern for entity operations with eager loading
 */
class EntityRepository
{
    private EntityManager $entityManager;
    private QueryFactory $queryFactory;
    private QueryCache $queryCache;

    public function __construct(
        EntityManager $entityManager,
        QueryFactory $queryFactory,
        QueryCache $queryCache
    ) {
        $this->entityManager = $entityManager;
        $this->queryFactory = $queryFactory;
        $this->queryCache = $queryCache;
    }

    /**
     * Find entity by ID
     */
    public function find(int $id, bool $loadValues = true): ?Entity
    {
        return $this->entityManager->find($id, $loadValues);
    }

    /**
     * Find multiple entities
     */
    public function findMany(array $ids, bool $loadValues = true): array
    {
        return $this->entityManager->findMany($ids, $loadValues);
    }

    /**
     * Create a new entity
     */
    public function create(int $entityTypeId, array $data, ?int $parentId = null): Entity
    {
        return $this->entityManager->create($entityTypeId, $data, $parentId);
    }

    /**
     * Update an entity
     */
    public function update(int $id, array $data): bool
    {
        return $this->entityManager->update($id, $data);
    }

    /**
     * Delete an entity
     */
    public function delete(int $id, bool $soft = true): bool
    {
        return $this->entityManager->delete($id, $soft);
    }

    /**
     * Create query builder for entity type
     */
    public function query(int $entityTypeId): EavQueryBuilder
    {
        return $this->queryFactory->forEntityType($entityTypeId);
    }

    /**
     * Create query builder by entity type code
     */
    public function queryByCode(string $entityTypeCode): EavQueryBuilder
    {
        return $this->queryFactory->forEntityTypeCode($entityTypeCode);
    }

    /**
     * Find entities by attribute value
     */
    public function findByAttribute(int $entityTypeId, string $attributeCode, mixed $value): array
    {
        return $this->query($entityTypeId)
            ->where($attributeCode, '=', $value)
            ->get();
    }

    /**
     * Find first entity by attribute value
     */
    public function findFirstByAttribute(int $entityTypeId, string $attributeCode, mixed $value): ?Entity
    {
        return $this->query($entityTypeId)
            ->where($attributeCode, '=', $value)
            ->first();
    }

    /**
     * Search entities
     */
    public function search(int $entityTypeId, array $criteria, ?int $limit = null): array
    {
        $query = $this->query($entityTypeId);

        foreach ($criteria as $attributeCode => $value) {
            if (is_array($value)) {
                // Handle array values (IN queries)
                $query->whereIn($attributeCode, $value);
            } else {
                $query->where($attributeCode, '=', $value);
            }
        }

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Search with LIKE operator
     */
    public function searchLike(int $entityTypeId, string $attributeCode, string $searchTerm, ?int $limit = null): array
    {
        $query = $this->query($entityTypeId)
            ->whereLike($attributeCode, "%{$searchTerm}%");

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get entities with pagination
     */
    public function paginate(int $entityTypeId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $query = $this->query($entityTypeId);
        
        $total = $query->count();
        $entities = $query->limit($perPage)->offset($offset)->get();

        return [
            'data' => $entities,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
            'has_more' => $offset + $perPage < $total
        ];
    }

    /**
     * Get all entities of a type
     */
    public function all(int $entityTypeId, ?int $limit = null): array
    {
        $query = $this->query($entityTypeId);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Count entities of a type
     */
    public function count(int $entityTypeId): int
    {
        return $this->entityManager->countByType($entityTypeId);
    }

    /**
     * Get entities with specific attributes
     */
    public function withAttributes(int $entityTypeId, array $attributeCodes, ?int $limit = null): array
    {
        $query = $this->query($entityTypeId)->select($attributeCodes);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get entities ordered by attribute
     */
    public function orderBy(int $entityTypeId, string $attributeCode, string $direction = 'ASC', ?int $limit = null): array
    {
        $query = $this->query($entityTypeId)->orderBy($attributeCode, $direction);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Find entities where attribute is in range
     */
    public function whereBetween(int $entityTypeId, string $attributeCode, $min, $max): array
    {
        return $this->query($entityTypeId)
            ->whereBetween($attributeCode, $min, $max)
            ->get();
    }

    /**
     * Find entities where attribute is in array
     */
    public function whereIn(int $entityTypeId, string $attributeCode, array $values): array
    {
        return $this->query($entityTypeId)
            ->whereIn($attributeCode, $values)
            ->get();
    }

    /**
     * Copy an entity
     */
    public function copy(int $sourceId, ?array $overrideData = null): ?Entity
    {
        return $this->entityManager->copy($sourceId, $overrideData);
    }

    /**
     * Check if entity exists
     */
    public function exists(int $id): bool
    {
        return $this->entityManager->exists($id);
    }

    /**
     * Get or create entity
     */
    public function firstOrCreate(int $entityTypeId, array $criteria, array $data = []): Entity
    {
        // Try to find existing
        $entity = $this->search($entityTypeId, $criteria, 1);

        if (!empty($entity)) {
            return $entity[0];
        }

        // Create new
        $createData = array_merge($criteria, $data);
        return $this->create($entityTypeId, $createData);
    }

    /**
     * Update or create entity
     */
    public function updateOrCreate(int $entityTypeId, array $criteria, array $data): Entity
    {
        $entity = $this->search($entityTypeId, $criteria, 1);

        if (!empty($entity)) {
            $this->update($entity[0]->id, $data);
            return $this->find($entity[0]->id);
        }

        $createData = array_merge($criteria, $data);
        return $this->create($entityTypeId, $createData);
    }

    /**
     * Bulk update entities
     */
    public function bulkUpdate(array $entityIds, array $data): int
    {
        $count = 0;
        foreach ($entityIds as $id) {
            if ($this->update($id, $data)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Bulk delete entities
     */
    public function bulkDelete(array $entityIds, bool $soft = true): int
    {
        $count = 0;
        foreach ($entityIds as $id) {
            if ($this->delete($id, $soft)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get children of an entity
     */
    public function getChildren(int $parentId, bool $loadValues = true): array
    {
        $parent = $this->find($parentId, false);
        if (!$parent) {
            return [];
        }

        return $this->query($parent->entity_type_id)
            ->where('parent_id', '=', $parentId)
            ->get();
    }

    /**
     * Get active entities
     */
    public function getActive(int $entityTypeId, ?int $limit = null): array
    {
        $query = $this->query($entityTypeId)->where('is_active', '=', 1);

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }
}
