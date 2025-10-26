<?php

namespace Core\Database\Relations;

use Core\Database\Model;

/**
 * Morph One Relationship (Polymorphic One-to-One)
 * Example: Post morphOne Image (where Image can belong to Post, User, Product, etc.)
 */
class MorphOne extends Relation
{
    protected string $morphType;
    protected string $morphId;
    protected string $localKey;

    public function __construct(Model $parent, string $related, string $name, ?string $localKey = null)
    {
        parent::__construct($parent, $related);
        
        $this->morphType = $name . '_type';
        $this->morphId = $name . '_id';
        $this->localKey = $localKey ?? 'id';
        
        $this->addConstraints();
    }

    /**
     * Add constraints to the query
     */
    public function addConstraints(): void
    {
        if ($this->parent->exists()) {
            $this->query->where($this->morphType, '=', get_class($this->parent))
                       ->where($this->morphId, '=', $this->parent->{$this->localKey});
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
        
        $this->query->where($this->morphType, '=', get_class($this->parent))
                   ->whereIn($this->morphId, $keys);
    }

    /**
     * Match the eagerly loaded results to their parents
     */
    public function match(array $models, array $results, string $relation): array
    {
        $dictionary = [];
        
        foreach ($results as $result) {
            $dictionary[$result->{$this->morphId}] = $result;
        }
        
        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->{$this->localKey}])) {
                $model->setRelation($relation, $dictionary[$key]);
            }
        }
        
        return $models;
    }

    /**
     * Get the results of the relationship
     */
    public function get()
    {
        return $this->query->first();
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
        $attributes[$this->morphType] = get_class($this->parent);
        $attributes[$this->morphId] = $this->parent->{$this->localKey};
        return (new $this->related)->create($attributes);
    }
}
