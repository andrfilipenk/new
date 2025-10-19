<?php
// app/Eav/Query/QueryFactory.php
namespace Eav\Query;

use Core\Database\Database;
use Eav\Repositories\AttributeRepository;

/**
 * Query Factory
 * 
 * Factory for creating pre-configured query builders for entity types
 */
class QueryFactory
{
    private Database $db;
    private AttributeRepository $attributeRepository;
    private JoinOptimizer $joinOptimizer;
    private FilterTranslator $filterTranslator;

    public function __construct(
        Database $db,
        AttributeRepository $attributeRepository,
        JoinOptimizer $joinOptimizer,
        FilterTranslator $filterTranslator
    ) {
        $this->db = $db;
        $this->attributeRepository = $attributeRepository;
        $this->joinOptimizer = $joinOptimizer;
        $this->filterTranslator = $filterTranslator;
    }

    /**
     * Create query builder for entity type ID
     */
    public function forEntityType(int $entityTypeId): EavQueryBuilder
    {
        return new EavQueryBuilder(
            $this->db,
            $entityTypeId,
            $this->attributeRepository,
            $this->joinOptimizer,
            $this->filterTranslator
        );
    }

    /**
     * Create query builder for entity type code
     */
    public function forEntityTypeCode(string $entityTypeCode): EavQueryBuilder
    {
        $entityType = $this->attributeRepository->getEntityTypeByCode($entityTypeCode);
        
        if (!$entityType) {
            throw new \InvalidArgumentException("Entity type '{$entityTypeCode}' not found");
        }

        return $this->forEntityType($entityType->id);
    }

    /**
     * Create query builder with filters pre-applied
     */
    public function createWithFilters(int $entityTypeId, array $filters): EavQueryBuilder
    {
        $builder = $this->forEntityType($entityTypeId);

        foreach ($filters as $filter) {
            if (isset($filter['attribute']) && isset($filter['operator'])) {
                $builder->where(
                    $filter['attribute'],
                    $filter['operator'],
                    $filter['value'] ?? null
                );
            }
        }

        return $builder;
    }
}
