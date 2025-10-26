<?php

namespace Core\Database\Relations;

use Core\Database\Model;

/**
 * Belongs To Relationship
 * Example: Comment belongsTo Post
 */
class BelongsTo extends Relation
{
    protected string $foreignKey;
    protected string $ownerKey;

    public function __construct(Model $parent, string $related, ?string $foreignKey = null, ?string $ownerKey = null)
    {
        parent::__construct($parent, $related);
        
        $this->foreignKey = $foreignKey ?? $this->getForeignKey();
        $this->ownerKey = $ownerKey ?? 'id';
        
        $this->addConstraints();
    }

    /**
     * Add constraints to the query
     */
    public function addConstraints(): void
    {
        if ($this->parent->exists()) {
            $this->query->where($this->ownerKey, '=', $this->parent->{$this->foreignKey});
        }
    }

    /**
     * Add eager loading constraints
     */
    public function addEagerConstraints(array $models): void
    {
        $keys = array_unique(array_filter(array_map(function($model) {
            return $model->{$this->foreignKey};
        }, $models)));
        
        if (!empty($keys)) {
            $this->query->whereIn($this->ownerKey, $keys);
        }
    }

    /**
     * Match the eagerly loaded results to their parents
     */
    public function match(array $models, array $results, string $relation): array
    {
        $dictionary = [];
        
        foreach ($results as $result) {
            $dictionary[$result->{$this->ownerKey}] = $result;
        }
        
        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->{$this->foreignKey}])) {
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
     * Associate the model with the parent
     */
    public function associate(Model $model): Model
    {
        $this->parent->{$this->foreignKey} = $model->{$this->ownerKey};
        return $this->parent;
    }

    /**
     * Dissociate the model from the parent
     */
    public function dissociate(): Model
    {
        $this->parent->{$this->foreignKey} = null;
        return $this->parent;
    }

    /**
     * Get the default foreign key name
     */
    protected function getForeignKey(): string
    {
        $relatedClass = $this->related;
        $baseName = class_basename($relatedClass);
        return strtolower($baseName) . '_id';
    }
}
