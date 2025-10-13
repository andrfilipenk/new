<?php
// app/Core/Database/QueryBuilder.php
namespace Core\Database;

class QueryBuilder
{
    private string $model;
    private array $relations;
    private Database $query;
    private array $relationFilters = [];

    public function __construct(string $model, array $relations = [])
    {
        $this->model        = $model;
        $this->relations    = $this->normalizeRelations($relations);
        $this->query        = $this->model::query();
    }

    public function where($column, mixed $operator = null, mixed $value = null): self
    {
        $this->query->where($column, $operator, $value);
        return $this;
    }

    public function whereIn($column, array $values): self
    {
        $this->query->whereIn($column, $values);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->query->limit($limit);
        return $this;
    }

    public function selectRaw(string $expression): self
    {
        $this->query->selectRaw($expression);
        return $this;
    }

    public function count(): int
    {
        return $this->query->select(['COUNT(*)'])->first()['COUNT(*)'] ?? 0;
    }

    public function offset(int $offset): self
    {
        $this->query->offset($offset);
        return $this;
    }

    public function whereNotIn(string $column, array $values): self
    {
        $this->query->whereNotIn($column, $values);
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->query->whereNull($column);
        return $this;
    }

    public function whereNotNull(string $column): self
    {
        $this->query->whereNotNull($column);
        return $this;
    }

    public function whereRaw(string $sql, array $bindings = []): self
    {
        $this->query->whereRaw($sql, $bindings);
        return $this;
    }

    public function andWhere($column, mixed $operator = null, mixed $value = null): self
    {
        $this->query->where($column, $operator, $value);
        return $this;
    }

    public function orWhere($column, mixed $operator = null, mixed $value = null): self
    {
        $this->query->orWhere($column, $operator, $value);
        return $this;
    }

    public function groupBy(string $column): self
    {
        $this->query->groupBy($column);
        return $this;
    }

    public function having(string $column, string $operator = null, $value = null): self
    {
        $this->query->having($column, $operator, $value);
        return $this;
    }

    public function join(string $table, string $first, string $operator = null, string $second = null): self
    {
        $this->query->join($table, $first, $operator, $second);
        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator = null, string $second = null): self
    {
        $this->query->leftJoin($table, $first, $operator, $second);
        return $this;
    }

    public function select(array $columns = ['*']): self
    {
        $this->query->select($columns);
        return $this;
    }

    /**
     * Apply filters to the main query before loading relations
     * This allows custom filtering on the base model before eager loading
     */
    public function withFilters(callable $callback): self
    {
        $callback($this->query);
        return $this;
    }

    /**
     * Add custom where conditions with relation context
     * Useful for filtering before eager loading with joins
     */
    public function whereWithRelation(string $relation, callable $callback): self
    {
        // Store the relation filter for later application
        if (!isset($this->relationFilters)) {
            $this->relationFilters = [];
        }
        $this->relationFilters[$relation] = $callback;
        return $this;
    }

    public function get(): array
    {
        $results = $this->query->get();
        $models = array_map([$this->model, 'newFromBuilder'], $results);
        if (!empty($this->relations) && !empty($models)) {
            $this->eagerLoadRelations($models, $this->relations);
        }
        return $models;
    }

    public function first(): ?object
    {
        $result = $this->query->first();
        if (!$result) return null;
        $model = $this->model::newFromBuilder($result);
        if (!empty($this->relations)) {
            $this->eagerLoadRelations([$model], $this->relations);
        }
        return $model;
    }

    /**
     * Normalize relation input to array with constraints
     */
    private function normalizeRelations(array $relations): array
    {
        $normalized = [];
        foreach ($relations as $key => $value) {
            if (is_int($key)) {
                // ['posts', 'profile']
                $normalized[$value] = null;
            } else {
                // ['posts' => $fn]
                $normalized[$key] = $value;
            }
        }
        return $normalized;
    }

    /**
     * Eager load relations, recursively if needed
     */
    private function eagerLoadRelations(array $models, array $relations): void
    {
        foreach ($relations as $relation => $constraint) {
            $nested = explode('.', $relation, 2);
            $name = $nested[0];
            $nestedRelations = isset($nested[1]) ? [$nested[1] => $constraint] : [];
            // build dictionary for nested relations
            $relationInstances = [];
            foreach ($models as $model) {
                if (!method_exists($model, $name)) continue;
                $relationInstances[] = $model->$name();
            }
            if (empty($relationInstances)) continue;
            $relationInstance = $relationInstances[0];
            // Apply eager constraints
            $relationInstance->addEagerConstraints($models);
            
            // Apply custom relation filters if any
            if (isset($this->relationFilters[$name])) {
                $this->relationFilters[$name]($relationInstance->getQuery());
            }
            
            if ($constraint && is_callable($constraint)) {
                $constraint($relationInstance->getQuery());
            }
            $results = $relationInstance->getQuery()->get();
            $relationModels = array_map(
                [get_class($relationInstance->getRelatedInstance()), 'newFromBuilder'],
                $results
            );
            // Match models to parents
            $relationInstance->match($models, $relationModels, $name);
            // Nested eager load
            if ($nestedRelations) {
                // Gather all related models (flatten array if hasMany/belongsToMany)
                $related = [];
                foreach ($models as $model) {
                    $rel = $model->getData($name);
                    if (is_array($rel)) {
                        foreach ($rel as $item) $related[] = $item;
                    } elseif ($rel) {
                        $related[] = $rel;
                    }
                }
                if (!empty($related)) {
                    $this->eagerLoadRelations($related, $nestedRelations);
                }
            }
        }
    }
}