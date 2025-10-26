<?php

namespace Core\Database\Relations;

use Core\Database\Model;
use Core\Database\DB;

/**
 * Belongs To Many Relationship (Many-to-Many)
 * Example: User belongsToMany Role (via user_roles pivot table)
 */
class BelongsToMany extends Relation
{
    protected string $table;
    protected string $foreignPivotKey;
    protected string $relatedPivotKey;
    protected string $parentKey;
    protected string $relatedKey;
    protected array $pivotColumns = [];

    public function __construct(
        Model $parent,
        string $related,
        ?string $table = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null,
        ?string $parentKey = null,
        ?string $relatedKey = null
    ) {
        parent::__construct($parent, $related);
        
        $this->table = $table ?? $this->guessTableName();
        $this->foreignPivotKey = $foreignPivotKey ?? $this->getForeignPivotKey();
        $this->relatedPivotKey = $relatedPivotKey ?? $this->getRelatedPivotKey();
        $this->parentKey = $parentKey ?? 'id';
        $this->relatedKey = $relatedKey ?? 'id';
        
        $this->addConstraints();
    }

    /**
     * Add constraints to the query
     */
    public function addConstraints(): void
    {
        if ($this->parent->exists()) {
            $this->query->join(
                $this->table,
                $this->query->getTable() . '.' . $this->relatedKey,
                '=',
                $this->table . '.' . $this->relatedPivotKey
            )->where($this->table . '.' . $this->foreignPivotKey, '=', $this->parent->{$this->parentKey});
        }
    }

    /**
     * Add eager loading constraints
     */
    public function addEagerConstraints(array $models): void
    {
        $keys = array_unique(array_map(function($model) {
            return $model->{$this->parentKey};
        }, $models));
        
        $this->query->join(
            $this->table,
            $this->query->getTable() . '.' . $this->relatedKey,
            '=',
            $this->table . '.' . $this->relatedPivotKey
        )->whereIn($this->table . '.' . $this->foreignPivotKey, $keys);
    }

    /**
     * Match the eagerly loaded results to their parents
     */
    public function match(array $models, array $results, string $relation): array
    {
        $dictionary = [];
        
        foreach ($results as $result) {
            $key = $result->{$this->foreignPivotKey};
            if (!isset($dictionary[$key])) {
                $dictionary[$key] = [];
            }
            $dictionary[$key][] = $result;
        }
        
        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->{$this->parentKey}])) {
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
     * Specify which pivot columns to retrieve
     */
    public function withPivot(...$columns): self
    {
        $this->pivotColumns = array_merge($this->pivotColumns, $columns);
        return $this;
    }

    /**
     * Attach models to the relationship
     */
    public function attach($id, array $attributes = []): void
    {
        $ids = is_array($id) ? $id : [$id];
        
        foreach ($ids as $relatedId) {
            $pivotData = array_merge($attributes, [
                $this->foreignPivotKey => $this->parent->{$this->parentKey},
                $this->relatedPivotKey => $relatedId
            ]);
            
            DB::table($this->table)->insert($pivotData);
        }
    }

    /**
     * Detach models from the relationship
     */
    public function detach($ids = null): int
    {
        $query = DB::table($this->table)
            ->where($this->foreignPivotKey, '=', $this->parent->{$this->parentKey});
        
        if ($ids !== null) {
            $ids = is_array($ids) ? $ids : [$ids];
            $query->whereIn($this->relatedPivotKey, $ids);
        }
        
        return $query->delete();
    }

    /**
     * Sync the relationship (detach all and attach new)
     */
    public function sync(array $ids, bool $detaching = true): void
    {
        if ($detaching) {
            $this->detach();
        }
        
        if (!empty($ids)) {
            $this->attach($ids);
        }
    }

    /**
     * Toggle the relationship
     */
    public function toggle(array $ids): void
    {
        $current = $this->get();
        $currentIds = array_map(fn($model) => $model->{$this->relatedKey}, $current);
        
        $toDetach = array_intersect($ids, $currentIds);
        $toAttach = array_diff($ids, $currentIds);
        
        if (!empty($toDetach)) {
            $this->detach($toDetach);
        }
        
        if (!empty($toAttach)) {
            $this->attach($toAttach);
        }
    }

    /**
     * Update existing pivot records
     */
    public function updateExistingPivot($id, array $attributes): int
    {
        return DB::table($this->table)
            ->where($this->foreignPivotKey, '=', $this->parent->{$this->parentKey})
            ->where($this->relatedPivotKey, '=', $id)
            ->update($attributes);
    }

    /**
     * Guess the pivot table name
     */
    protected function guessTableName(): string
    {
        $parentClass = get_class($this->parent);
        $relatedClass = $this->related;
        
        $parentTable = (new $parentClass)->getTable();
        $relatedTable = (new $relatedClass)->getTable();
        
        $tables = [$parentTable, $relatedTable];
        sort($tables);
        
        return implode('_', $tables);
    }

    /**
     * Get the foreign pivot key name
     */
    protected function getForeignPivotKey(): string
    {
        return $this->parent->getTable() . '_id';
    }

    /**
     * Get the related pivot key name
     */
    protected function getRelatedPivotKey(): string
    {
        return (new $this->related)->getTable() . '_id';
    }
}
