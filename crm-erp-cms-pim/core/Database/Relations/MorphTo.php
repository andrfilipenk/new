<?php

namespace Core\Database\Relations;

use Core\Database\Model;

/**
 * Morph To Relationship (Polymorphic Inverse)
 * Example: Comment morphTo commentable (can belong to Post, Video, etc.)
 */
class MorphTo extends Relation
{
    protected string $morphType;
    protected string $morphId;
    protected array $morphMap = [];

    public function __construct(Model $parent, string $name)
    {
        $this->parent = $parent;
        $this->morphType = $name . '_type';
        $this->morphId = $name . '_id';
        
        // Don't call parent constructor as we don't know the related class yet
        $this->resolveRelatedClass();
    }

    /**
     * Resolve the related class from the morph type
     */
    protected function resolveRelatedClass(): void
    {
        $morphType = $this->parent->{$this->morphType};
        
        if (!$morphType) {
            return;
        }
        
        // Use morph map if defined, otherwise use the type directly
        $this->related = $this->morphMap[$morphType] ?? $morphType;
        $this->query = (new $this->related)->newQuery();
    }

    /**
     * Set the morph map for polymorphic relations
     */
    public static function morphMap(array $map): void
    {
        static $instance;
        if (!$instance) {
            $instance = new static(new class extends Model {}, '');
        }
        $instance->morphMap = $map;
    }

    /**
     * Add constraints to the query
     */
    public function addConstraints(): void
    {
        if ($this->parent->exists() && $this->query) {
            $this->query->where('id', '=', $this->parent->{$this->morphId});
        }
    }

    /**
     * Add eager loading constraints
     */
    public function addEagerConstraints(array $models): void
    {
        // Group models by type
        $grouped = [];
        foreach ($models as $model) {
            $type = $model->{$this->morphType};
            if ($type) {
                $grouped[$type][] = $model->{$this->morphId};
            }
        }
        
        // This is complex and typically handled differently in real implementations
        // For now, we'll keep it simple
    }

    /**
     * Match the eagerly loaded results to their parents
     */
    public function match(array $models, array $results, string $relation): array
    {
        $dictionary = [];
        
        foreach ($results as $result) {
            $key = get_class($result) . ':' . $result->id;
            $dictionary[$key] = $result;
        }
        
        foreach ($models as $model) {
            $type = $model->{$this->morphType};
            $id = $model->{$this->morphId};
            
            if ($type && $id) {
                $key = $type . ':' . $id;
                if (isset($dictionary[$key])) {
                    $model->setRelation($relation, $dictionary[$key]);
                }
            }
        }
        
        return $models;
    }

    /**
     * Get the results of the relationship
     */
    public function get()
    {
        return $this->query ? $this->query->first() : null;
    }

    /**
     * Get the first result of the relationship
     */
    public function first()
    {
        return $this->query ? $this->query->first() : null;
    }

    /**
     * Associate the model with the parent
     */
    public function associate(Model $model): Model
    {
        $this->parent->{$this->morphType} = get_class($model);
        $this->parent->{$this->morphId} = $model->id;
        return $this->parent;
    }

    /**
     * Dissociate the model from the parent
     */
    public function dissociate(): Model
    {
        $this->parent->{$this->morphType} = null;
        $this->parent->{$this->morphId} = null;
        return $this->parent;
    }
}
