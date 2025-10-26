<?php

namespace Core\Database\Relations;

use Core\Database\Model;

/**
 * Has One Relationship
 * Example: User hasOne Profile
 */
class HasOne extends Relation
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
            $dictionary[$result->{$this->foreignKey}] = $result;
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
     * Get the default foreign key name
     */
    protected function getForeignKey(): string
    {
        $parentClass = get_class($this->parent);
        $baseName = class_basename($parentClass);
        return strtolower($baseName) . '_id';
    }
}
