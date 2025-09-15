<?php
namespace Core\Database;

// Eager loading support
class QueryBuilder
{
    private string $model;
    private array $relations;

    public function __construct(string $model, array $relations = [])
    {
        $this->model = $model;
        $this->relations = $relations;
    }

    public function get(): array
    {
        $models = array_map([$this->model, 'newFromBuilder'], $this->model::query()->get());
        
        if (!empty($this->relations)) {
            $this->eagerLoadRelations($models);
        }

        return $models;
    }

    private function eagerLoadRelations(array $models): void
    {
        foreach ($this->relations as $relation) {
            $this->loadRelation($models, $relation);
        }
    }

    private function loadRelation(array $models, string $relation): void
    {
        $relationInstance = $models[0]->$relation();
        $relationInstance->addEagerConstraints($models);
        $results = $relationInstance->getQuery()->get();
        $results = array_map([$relationInstance->getRelated()::class, 'newFromBuilder'], $results);
        $relationInstance->match($models, $results, $relation);
    }
}