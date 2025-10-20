<?php
// app/Eav/Query/FilterTranslator.php
namespace Eav\Query;

use Eav\Models\Attribute;

/**
 * Filter Translator
 * 
 * Translates high-level filters to SQL conditions for EAV queries
 */
class FilterTranslator
{
    /**
     * Translate filters to SQL conditions
     */
    public function translate(array $filters, array $attributes): array
    {
        $conditions = [];
        $bindings = [];

        foreach ($filters as $filter) {
            $result = $this->translateFilter($filter, $attributes);
            if ($result) {
                $conditions[] = $result['condition'];
                $bindings = array_merge($bindings, $result['bindings']);
            }
        }

        return [
            'conditions' => $conditions,
            'bindings' => $bindings
        ];
    }

    /**
     * Translate a single filter
     */
    private function translateFilter(array $filter, array $attributes): ?array
    {
        $attributeCode = $filter['attribute'] ?? null;
        $operator = $filter['operator'] ?? '=';
        $value = $filter['value'] ?? null;

        if (!$attributeCode) {
            return null;
        }

        // Find attribute
        $attribute = $this->findAttribute($attributeCode, $attributes);
        if (!$attribute) {
            return null;
        }

        $tableName = $this->getTableName($attribute->backend_type);
        $tableAlias = "val_{$attribute->id}";

        return $this->buildCondition($tableAlias, $operator, $value, $attribute);
    }

    /**
     * Build SQL condition based on operator
     */
    private function buildCondition(string $tableAlias, string $operator, mixed $value, Attribute $attribute): array
    {
        $operator = strtoupper($operator);

        return match($operator) {
            '=' => [
                'condition' => "{$tableAlias}.value = ?",
                'bindings' => [$value]
            ],
            '!=' => [
                'condition' => "{$tableAlias}.value != ?",
                'bindings' => [$value]
            ],
            '>' => [
                'condition' => "{$tableAlias}.value > ?",
                'bindings' => [$value]
            ],
            '>=' => [
                'condition' => "{$tableAlias}.value >= ?",
                'bindings' => [$value]
            ],
            '<' => [
                'condition' => "{$tableAlias}.value < ?",
                'bindings' => [$value]
            ],
            '<=' => [
                'condition' => "{$tableAlias}.value <= ?",
                'bindings' => [$value]
            ],
            'LIKE' => [
                'condition' => "{$tableAlias}.value LIKE ?",
                'bindings' => [$value]
            ],
            'IN' => [
                'condition' => "{$tableAlias}.value IN (" . implode(',', array_fill(0, count($value), '?')) . ")",
                'bindings' => $value
            ],
            'NOT IN' => [
                'condition' => "{$tableAlias}.value NOT IN (" . implode(',', array_fill(0, count($value), '?')) . ")",
                'bindings' => $value
            ],
            'IS NULL' => [
                'condition' => "{$tableAlias}.value IS NULL",
                'bindings' => []
            ],
            'IS NOT NULL' => [
                'condition' => "{$tableAlias}.value IS NOT NULL",
                'bindings' => []
            ],
            'BETWEEN' => [
                'condition' => "{$tableAlias}.value BETWEEN ? AND ?",
                'bindings' => [$value[0], $value[1]]
            ],
            default => [
                'condition' => "{$tableAlias}.value = ?",
                'bindings' => [$value]
            ]
        };
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
     * Build complex filter with AND/OR logic
     */
    public function translateComplexFilter(array $filter, array $attributes): array
    {
        if (isset($filter['and'])) {
            return $this->translateAndFilter($filter['and'], $attributes);
        }

        if (isset($filter['or'])) {
            return $this->translateOrFilter($filter['or'], $attributes);
        }

        // Simple filter
        $result = $this->translateFilter($filter, $attributes);
        return $result ?? ['conditions' => [], 'bindings' => []];
    }

    /**
     * Translate AND filter
     */
    private function translateAndFilter(array $filters, array $attributes): array
    {
        $conditions = [];
        $bindings = [];

        foreach ($filters as $filter) {
            $result = $this->translateComplexFilter($filter, $attributes);
            if (!empty($result['conditions'])) {
                $conditions[] = '(' . implode(' AND ', $result['conditions']) . ')';
                $bindings = array_merge($bindings, $result['bindings']);
            }
        }

        return [
            'conditions' => $conditions,
            'bindings' => $bindings
        ];
    }

    /**
     * Translate OR filter
     */
    private function translateOrFilter(array $filters, array $attributes): array
    {
        $conditions = [];
        $bindings = [];

        foreach ($filters as $filter) {
            $result = $this->translateComplexFilter($filter, $attributes);
            if (!empty($result['conditions'])) {
                $conditions[] = '(' . implode(' AND ', $result['conditions']) . ')';
                $bindings = array_merge($bindings, $result['bindings']);
            }
        }

        return [
            'conditions' => [implode(' OR ', $conditions)],
            'bindings' => $bindings
        ];
    }
}
