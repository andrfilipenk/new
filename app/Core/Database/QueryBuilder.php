<?php
namespace Core\Database;

class QueryBuilder
{
    private string $model;
    private array $relations;
    private Database $query;

    public function __construct(string $model, array $relations = [])
    {
        $this->model = $model;
        $this->relations = $this->normalizeRelations($relations);
        $this->query = $this->model::query();
    }

    public function where(string $column, mixed $operator = null, mixed $value = null): self
    {
        $this->query->where($column, $operator, $value);
        return $this;
    }

    public function whereIn(string $column, array $values): self
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