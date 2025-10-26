<?php

namespace Core\Database\Relations;

use Core\Database\Model;

/**
 * Has Many Relationship
 * Example: Post hasMany Comments
 */
class HasMany extends Relation
{
    protected string $foreignKey;
    protected string $localKey;

    public function __construct(Model $parent, string $related, ?string $foreignKey = null, ?string $localKey = null)
    {
        parent::__construct($parent, $related);
        
        $this->foreignKey = $foreignKey ?? $this->getForeignKey();
        $this->localKey = $localKey ?? 'id';
        
        $this->addConstraints();
    }

    /**
     * Add constraints to the query
     */
    public function addConstraints(): void
    {
        if ($this->parent->exists()) {
            $this->query->where($this->foreignKey, '=', $this->parent->{$this->localKey});
        }
    }

    /**
     * Add eager loading constraints
     */
    public function addEagerConstraints(array $models): void
    {
        $keys = array_unique(array_map(function($model) {
            return $model->{$this->localKey};
        }, $models));
        
        $this->query->whereIn($this->foreignKey, $keys);
    }

    /**
     * Match the eagerly loaded results to their parents
     */
    public function match(array $models, array $results, string $relation): array
    {
        $dictionary = [];
        
        foreach ($results as $result) {
            $key = $result->{$this->foreignKey};
            if (!isset($dictionary[$key])) {
                $dictionary[$key] = [];
            }
            $dictionary[$key][] = $result;
        }
        
        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->{$this->localKey}])) {
                $model->setRelation($relation, $dictionary[$key]);
            } else {
                $model->setRelation($relation, []);
            }
        }
        
        return $models;
    }

    /**
     * Get the results of the relationship
     */
    public function get()
    {
        return $this->query->get();
    }

    /**
     * Get the first result of the relationship
     */
    public function first()
    {
        return $this->query->first();
    }

    /**
     * Create a new related model
     */
    public function create(array $attributes = []): Model
    {
        $attributes[$this->foreignKey] = $this->parent->{$this->localKey};
        return (new $this->related)->create($attributes);
    }

    /**
     * Save a related model
     */
    public function save(Model $model): bool
    {
        $model->{$this->foreignKey} = $this->parent->{$this->localKey};
        return $model->save();
    }

    /**
     * Get the default foreign key name
     */
    protected function getForeignKey(): string
    {
        $parentClass = get_class($this->parent);
        $baseName = class_basename($parentClass);
        return strtolower($baseName) . '_id';
    }
}
