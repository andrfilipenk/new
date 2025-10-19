<?php
// app/Eav/Query/JoinOptimizer.php
namespace Eav\Query;

use Eav\Models\Attribute;

/**
 * Join Optimizer
 * 
 * Optimizes joins across value tables and minimizes query complexity
 */
class JoinOptimizer
{
    private int $maxJoins = 10;

    /**
     * Optimize joins for EAV query
     */
    public function optimizeJoins(array $attributes, array $filters = []): array
    {
        // Group attributes by backend type
        $attributesByType = $this->groupByBackendType($attributes);

        // Determine which attributes need joins based on filters
        $requiredAttributes = $this->getRequiredAttributes($attributes, $filters);

        // Build optimized join plan
        $joins = [];
        $joinCount = 0;

        foreach ($requiredAttributes as $attribute) {
            if ($joinCount >= $this->maxJoins) {
                // Use subquery strategy for remaining attributes
                break;
            }

            $joins[] = $this->buildJoin($attribute);
            $joinCount++;
        }

        return [
            'joins' => $joins,
            'use_subquery' => $joinCount >= $this->maxJoins,
            'remaining_attributes' => array_slice($requiredAttributes, $joinCount)
        ];
    }

    /**
     * Build JOIN clause for an attribute
     */
    public function buildJoin(Attribute $attribute): array
    {
        $tableName = $this->getTableName($attribute->backend_type);
        $alias = $this->getTableAlias($attribute->id);

        return [
            'type' => 'LEFT',
            'table' => $tableName,
            'alias' => $alias,
            'on' => "e.id = {$alias}.entity_id AND {$alias}.attribute_id = {$attribute->id}",
            'attribute_id' => $attribute->id,
            'attribute_code' => $attribute->attribute_code,
            'backend_type' => $attribute->backend_type
        ];
    }

    /**
     * Build optimized join for multiple attributes of same type
     */
    public function buildBatchJoin(array $attributes): array
    {
        if (empty($attributes)) {
            return [];
        }

        // All attributes must have same backend type
        $backendType = $attributes[0]->backend_type;
        $tableName = $this->getTableName($backendType);
        
        $attributeIds = array_map(fn($attr) => $attr->id, $attributes);
        $alias = "val_batch_{$backendType}";

        return [
            'type' => 'LEFT',
            'table' => $tableName,
            'alias' => $alias,
            'on' => "e.id = {$alias}.entity_id AND {$alias}.attribute_id IN (" . implode(',', $attributeIds) . ")",
            'attribute_ids' => $attributeIds,
            'backend_type' => $backendType,
            'is_batch' => true
        ];
    }

    /**
     * Determine if should use subquery strategy
     */
    public function shouldUseSubquery(int $attributeCount): bool
    {
        return $attributeCount > $this->maxJoins;
    }

    /**
     * Build subquery for attribute values
     */
    public function buildSubquery(Attribute $attribute): string
    {
        $tableName = $this->getTableName($attribute->backend_type);
        
        return "(SELECT value FROM {$tableName} 
                 WHERE entity_id = e.id AND attribute_id = {$attribute->id} 
                 LIMIT 1) AS {$attribute->attribute_code}";
    }

    /**
     * Optimize filter joins - only join tables needed for filtering
     */
    public function optimizeFilterJoins(array $filters, array $attributes): array
    {
        $requiredJoins = [];
        
        foreach ($filters as $filter) {
            $attributeCode = $filter['attribute'] ?? null;
            if (!$attributeCode) {
                continue;
            }

            $attribute = $this->findAttribute($attributeCode, $attributes);
            if ($attribute) {
                $requiredJoins[$attribute->id] = $this->buildJoin($attribute);
            }
        }

        return array_values($requiredJoins);
    }

    /**
     * Group attributes by backend type
     */
    private function groupByBackendType(array $attributes): array
    {
        $grouped = [];
        
        foreach ($attributes as $attribute) {
            $type = $attribute->backend_type;
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $attribute;
        }

        return $grouped;
    }

    /**
     * Get attributes required for query (used in filters or selected)
     */
    private function getRequiredAttributes(array $attributes, array $filters): array
    {
        $required = [];
        $filterAttributes = [];

        // Get attributes from filters
        foreach ($filters as $filter) {
            $attributeCode = $filter['attribute'] ?? null;
            if ($attributeCode) {
                $filterAttributes[] = $attributeCode;
            }
        }

        // Include filtered attributes first (higher priority)
        foreach ($attributes as $attribute) {
            if (in_array($attribute->attribute_code, $filterAttributes)) {
                $required[] = $attribute;
            }
        }

        // Then include other attributes
        foreach ($attributes as $attribute) {
            if (!in_array($attribute->attribute_code, $filterAttributes)) {
                $required[] = $attribute;
            }
        }

        return $required;
    }

    /**
     * Find attribute by code
     */
    private function findAttribute(string $code, array $attributes): ?Attribute
    {
        foreach ($attributes as $attribute) {
            if ($attribute->attribute_code === $code) {
                return $attribute;
            }
        }
        return null;
    }

    /**
     * Get table name for backend type
     */
    private function getTableName(string $backendType): string
    {
        return "eav_values_{$backendType}";
    }

    /**
     * Get table alias for attribute
     */
    private function getTableAlias(int $attributeId): string
    {
        return "val_{$attributeId}";
    }

    /**
     * Set maximum number of joins
     */
    public function setMaxJoins(int $max): void
    {
        $this->maxJoins = $max;
    }

    /**
     * Get join count estimate
     */
    public function estimateJoinCount(array $attributes, array $filters): int
    {
        $requiredAttributes = $this->getRequiredAttributes($attributes, $filters);
        return min(count($requiredAttributes), $this->maxJoins);
    }

    /**
     * Get table alias for attribute (public method)
     */
    public function getTableAlias(int $attributeId): string
    {
        return "val_{$attributeId}";
    }
}
