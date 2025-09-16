<?php
// app/Core/Database/QueryBuilder.php
namespace Core\Database;

class QueryBuilder
{
    private string $model;
    private array $relations;
    private Database $query;

    public function __construct(string $model, array $relations = [])
    {
        $this->model = $model;
        $this->relations = $relations;
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
            $this->eagerLoadRelations($models);
        }

        return $models;
    }

    public function first(): ?object
    {
        $result = $this->query->first();
        if (!$result) return null;

        $model = $this->model::newFromBuilder($result);

        if (!empty($this->relations)) {
            $this->eagerLoadRelations([$model]);
        }

        return $model;
    }

    private function eagerLoadRelations(array $models): void
    {
        foreach ($this->relations as $relation) {
            $this->loadRelation($models, $relation);
        }
    }

    private function loadRelation(array $models, string $relation): void
    {
        if (empty($models)) return;

        $relationInstance = $models[0]->$relation();
        $relationInstance->addEagerConstraints($models);

        $results = $relationInstance->getQuery()->get();
        $relationModels = array_map(
            [get_class($relationInstance->getRelated()), 'newFromBuilder'],
            $results
        );

        $relationInstance->match($models, $relationModels, $relation);
    }
}
