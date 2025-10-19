<?php
// app/Eav/Query/EavQueryBuilder.php
namespace Eav\Query;

use Core\Database\Database;
use Eav\Models\Attribute;
use Eav\Models\Entity;
use Eav\Repositories\AttributeRepository;

/**
 * EAV Query Builder
 * 
 * EAV-aware query builder with attribute filtering and value joins
 */
class EavQueryBuilder
{
    private Database $db;
    private AttributeRepository $attributeRepository;
    private JoinOptimizer $joinOptimizer;
    private FilterTranslator $filterTranslator;

    private int $entityTypeId;
    private array $attributes = [];
    private array $filters = [];
    private array $selectedAttributes = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $orderBy = [];
    private bool $loadAllAttributes = true;

    public function __construct(
        Database $db,
        int $entityTypeId,
        AttributeRepository $attributeRepository,
        JoinOptimizer $joinOptimizer,
        FilterTranslator $filterTranslator
    ) {
        $this->db = $db;
        $this->entityTypeId = $entityTypeId;
        $this->attributeRepository = $attributeRepository;
        $this->joinOptimizer = $joinOptimizer;
        $this->filterTranslator = $filterTranslator;

        // Load attributes for this entity type
        $this->attributes = $this->attributeRepository->getByEntityType($entityTypeId);
    }

    /**
     * Add filter condition
     */
    public function where(string $attributeCode, string $operator, mixed $value = null): self
    {
        if ($value === null && $operator !== 'IS NULL' && $operator !== 'IS NOT NULL') {
            $value = $operator;
            $operator = '=';
        }

        $this->filters[] = [
            'attribute' => $attributeCode,
            'operator' => $operator,
            'value' => $value
        ];

        return $this;
    }

    /**
     * Add OR filter condition
     */
    public function orWhere(array $conditions): self
    {
        $this->filters[] = [
            'or' => $conditions
        ];

        return $this;
    }

    /**
     * Add IN filter
     */
    public function whereIn(string $attributeCode, array $values): self
    {
        $this->filters[] = [
            'attribute' => $attributeCode,
            'operator' => 'IN',
            'value' => $values
        ];

        return $this;
    }

    /**
     * Add BETWEEN filter
     */
    public function whereBetween(string $attributeCode, $min, $max): self
    {
        $this->filters[] = [
            'attribute' => $attributeCode,
            'operator' => 'BETWEEN',
            'value' => [$min, $max]
        ];

        return $this;
    }

    /**
     * Add LIKE filter
     */
    public function whereLike(string $attributeCode, string $pattern): self
    {
        $this->filters[] = [
            'attribute' => $attributeCode,
            'operator' => 'LIKE',
            'value' => $pattern
        ];

        return $this;
    }

    /**
     * Select specific attributes
     */
    public function select(array $attributeCodes): self
    {
        $this->selectedAttributes = $attributeCodes;
        $this->loadAllAttributes = false;
        return $this;
    }

    /**
     * Set limit
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set offset
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Add order by
     */
    public function orderBy(string $attributeCode, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [
            'attribute' => $attributeCode,
            'direction' => strtoupper($direction)
        ];

        return $this;
    }

    /**
     * Execute query and get results
     */
    public function get(): array
    {
        $sql = $this->buildQuery();
        $bindings = $this->buildBindings();

        $results = $this->db->execute($sql, $bindings)->fetchAll();

        return $this->hydrate($results);
    }

    /**
     * Get first result
     */
    public function first(): ?Entity
    {
        $this->limit(1);
        $results = $this->get();

        return !empty($results) ? $results[0] : null;
    }

    /**
     * Count results
     */
    public function count(): int
    {
        $sql = $this->buildCountQuery();
        $bindings = $this->buildBindings();

        $result = $this->db->execute($sql, $bindings)->fetch();

        return $result['count'] ?? 0;
    }

    /**
     * Build the SQL query
     */
    private function buildQuery(): string
    {
        $sql = "SELECT DISTINCT e.id, e.entity_type_id, e.entity_code, e.is_active, e.created_at, e.updated_at";

        // Get attributes to load
        $attributesToLoad = $this->getAttributesToLoad();

        // Optimize joins
        $joinPlan = $this->joinOptimizer->optimizeJoins($attributesToLoad, $this->filters);

        // Add value columns
        if ($joinPlan['use_subquery']) {
            // Use subqueries for attributes
            foreach ($attributesToLoad as $attribute) {
                $sql .= ", " . $this->joinOptimizer->buildSubquery($attribute);
            }
        } else {
            // Use joins
            foreach ($joinPlan['joins'] as $join) {
                $sql .= ", {$join['alias']}.value AS attr_{$join['attribute_id']}";
            }
        }

        // FROM clause
        $sql .= " FROM eav_entities e";

        // Add JOINs
        if (!$joinPlan['use_subquery']) {
            foreach ($joinPlan['joins'] as $join) {
                $sql .= " {$join['type']} JOIN {$join['table']} {$join['alias']} ON {$join['on']}";
            }
        }

        // WHERE clause
        $sql .= " WHERE e.entity_type_id = ?";
        $sql .= " AND e.deleted_at IS NULL";

        // Add filter conditions
        if (!empty($this->filters)) {
            $filterJoins = $this->joinOptimizer->optimizeFilterJoins($this->filters, $this->attributes);
            
            foreach ($filterJoins as $join) {
                if (!$this->joinExists($join, $joinPlan['joins'])) {
                    $sql .= " {$join['type']} JOIN {$join['table']} {$join['alias']} ON {$join['on']}";
                }
            }

            $filterResult = $this->filterTranslator->translate($this->filters, $this->attributes);
            if (!empty($filterResult['conditions'])) {
                $sql .= " AND " . implode(' AND ', $filterResult['conditions']);
            }
        }

        // ORDER BY clause
        if (!empty($this->orderBy)) {
            $orderClauses = [];
            foreach ($this->orderBy as $order) {
                $attribute = $this->findAttribute($order['attribute']);
                if ($attribute) {
                    $alias = $this->joinOptimizer->getTableAlias($attribute->id);
                    $orderClauses[] = "{$alias}.value {$order['direction']}";
                }
            }
            if (!empty($orderClauses)) {
                $sql .= " ORDER BY " . implode(', ', $orderClauses);
            }
        }

        // LIMIT and OFFSET
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * Build count query
     */
    private function buildCountQuery(): string
    {
        $sql = "SELECT COUNT(DISTINCT e.id) as count FROM eav_entities e";

        // Add filter joins if needed
        if (!empty($this->filters)) {
            $filterJoins = $this->joinOptimizer->optimizeFilterJoins($this->filters, $this->attributes);
            
            foreach ($filterJoins as $join) {
                $sql .= " {$join['type']} JOIN {$join['table']} {$join['alias']} ON {$join['on']}";
            }
        }

        $sql .= " WHERE e.entity_type_id = ?";
        $sql .= " AND e.deleted_at IS NULL";

        // Add filter conditions
        if (!empty($this->filters)) {
            $filterResult = $this->filterTranslator->translate($this->filters, $this->attributes);
            if (!empty($filterResult['conditions'])) {
                $sql .= " AND " . implode(' AND ', $filterResult['conditions']);
            }
        }

        return $sql;
    }

    /**
     * Build bindings array
     */
    private function buildBindings(): array
    {
        $bindings = [$this->entityTypeId];

        if (!empty($this->filters)) {
            $filterResult = $this->filterTranslator->translate($this->filters, $this->attributes);
            $bindings = array_merge($bindings, $filterResult['bindings']);
        }

        return $bindings;
    }

    /**
     * Hydrate results into Entity objects
     */
    private function hydrate(array $results): array
    {
        $entities = [];

        foreach ($results as $row) {
            $entity = Entity::newFromBuilder([
                'id' => $row['id'],
                'entity_type_id' => $row['entity_type_id'],
                'entity_code' => $row['entity_code'],
                'is_active' => $row['is_active'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ]);

            // Extract attribute values
            $values = [];
            foreach ($this->attributes as $attribute) {
                $key = "attr_{$attribute->id}";
                if (isset($row[$key])) {
                    $values[$attribute->attribute_code] = $row[$key];
                } elseif (isset($row[$attribute->attribute_code])) {
                    $values[$attribute->attribute_code] = $row[$attribute->attribute_code];
                }
            }

            $entity->attributeValues = $values;
            $entities[] = $entity;
        }

        return $entities;
    }

    /**
     * Get attributes to load
     */
    private function getAttributesToLoad(): array
    {
        if (!$this->loadAllAttributes && !empty($this->selectedAttributes)) {
            $attributes = [];
            foreach ($this->selectedAttributes as $code) {
                $attribute = $this->findAttribute($code);
                if ($attribute) {
                    $attributes[] = $attribute;
                }
            }
            return $attributes;
        }

        return $this->attributes;
    }

    /**
     * Find attribute by code
     */
    private function findAttribute(string $code): ?Attribute
    {
        foreach ($this->attributes as $attribute) {
            if ($attribute->attribute_code === $code) {
                return $attribute;
            }
        }
        return null;
    }

    /**
     * Check if join already exists
     */
    private function joinExists(array $join, array $existingJoins): bool
    {
        foreach ($existingJoins as $existing) {
            if ($existing['alias'] === $join['alias']) {
                return true;
            }
        }
        return false;
    }
}
