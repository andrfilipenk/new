<?php
// app/Core/Database/Model/BelongsToMany.php
namespace Core\Database\Model;

use Core\Database\Model as DbModel;
use Core\Database\Database;

class BelongsToMany extends Relation
{
    protected $table;
    protected $foreignPivotKey;
    protected $relatedPivotKey;
    protected $pivotColumns = [];
    protected $joinAdded = false;

    public function __construct(DbModel $related, DbModel $parent, $table, $foreignPivotKey, $relatedPivotKey)
    {
        parent::__construct($related, $parent);
        $this->table           = $table;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relatedPivotKey = $relatedPivotKey;
    }

    /**
     * Add additional pivot columns to retrieve
     */
    public function withPivot(...$columns)
    {
        $this->pivotColumns = array_merge($this->pivotColumns, is_array($columns[0]) ? $columns[0] : $columns);
        return $this;
    }

    /**
     * Add where constraint to the relationship query
     */
    public function where($column, $operator = null, $value = null)
    {
        $this->addJoin();
        // If column doesn't contain table name, assume it's on the related table
        if (strpos($column, '.') === false) {
            $column = $this->related->getTable() . '.' . $column;
        }
        $this->query->where($column, $operator, $value);
        return $this;
    }

    /**
     * Add whereIn constraint to the relationship query
     */
    public function whereIn($column, array $values)
    {
        $this->addJoin();
        $this->query->whereIn($column, $values);
        return $this;
    }

    /**
     * Add pivot where constraint
     */
    public function wherePivot($column, $operator = null, $value = null)
    {
        $this->addJoin();
        $this->query->where($this->table . '.' . $column, $operator, $value);
        return $this;
    }

    public function getResults()
    {
        $this->addJoin();
        $results = $this->query
            ->where($this->table . '.' . $this->foreignPivotKey, $this->parent->getKey())
            ->get();
        return array_map(fn($row) => $this->related::newFromBuilder($row), $results);
    }

    public function addEagerConstraints(array $models)
    {
        $this->addJoin();
        $this->query->whereIn($this->table . '.' . $this->foreignPivotKey, $this->getKeys($models, $this->parent->getKeyName()));
    }

    public function match(array $models, array $results, $relation)
    {
        $dictionary = [];
        foreach ($results as $result) {
            // Extract pivot data - check for pivot columns with prefix
            $pivotKey = null;
            if (isset($result->{'pivot_' . $this->foreignPivotKey})) {
                $pivotKey = $result->{'pivot_' . $this->foreignPivotKey};
            } elseif (isset($result->{$this->foreignPivotKey})) {
                $pivotKey = $result->{$this->foreignPivotKey};
            } else {
                // Try to get from raw data
                $data = $result->getData();
                $pivotKey = $data['pivot_' . $this->foreignPivotKey] ?? $data[$this->foreignPivotKey] ?? null;
            }
            
            if ($pivotKey !== null) {
                $dictionary[$pivotKey][] = $result;
            }
        }
        
        foreach ($models as $model) {
            $key = $model->getKey();
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            } else {
                $model->setRelation($relation, []);
            }
        }
        return $models;
    }

    protected function addJoin()
    {
        if ($this->joinAdded) {
            return;
        }
        $this->joinAdded = true;
        $pivotColumns    = ['*'];
        if (!empty($this->pivotColumns)) {
            $pivotColumns = array_merge([$this->foreignPivotKey, $this->relatedPivotKey], $this->pivotColumns);
        }
        $pivotSelect     = [];
        foreach ($pivotColumns as $column) {
            if ($column === '*') {
                $pivotSelect[] = $this->table . '.*';
            } else {
                $pivotSelect[] = $this->table . '.' . $column . ' as pivot_' . $column;
            }
        }
        $this->query->select(array_merge(
            [$this->related->getTable() . '.*'],
            $pivotSelect
        ))->join(
            $this->table,
            $this->table . '.' . $this->relatedPivotKey,
            '=',
            $this->related->getTable() . '.' . $this->related->getKeyName()
        );
    }

    protected function getKeys(array $models, $key)
    {
        return array_unique(array_filter(array_map(function ($model) use ($key) {
            return $model->getData($key);
        }, $models)));
    }

    /**
     * Attach models to the relationship
     */
    public function attach($id, array $attributes = [])
    {
        if (is_array($id)) {
            foreach ($id as $singleId) {
                $this->attachSingle($singleId, $attributes);
            }
        } else {
            $this->attachSingle($id, $attributes);
        }
        return $this;
    }

    /**
     * Detach models from the relationship
     */
    public function detach($id = null)
    {
        $query = $this->newPivotQuery();
        $query->where($this->foreignPivotKey, $this->parent->getKey());
        if ($id !== null) {
            if (is_array($id)) {
                $query->whereIn($this->relatedPivotKey, $id);
            } else {
                $query->where($this->relatedPivotKey, $id);
            }
        }
        return $query->delete();
    }

    /**
     * Sync the relationship (attach new, detach missing)
     */
    public function sync(array $ids)
    {
        // Get current attached IDs
        $current = $this->newPivotQuery()
            ->where($this->foreignPivotKey, $this->parent->getKey())
            ->get();
        $currentIds = array_column($current, $this->relatedPivotKey);
        // Determine what to attach and detach
        $toAttach   = array_diff($ids, $currentIds);
        $toDetach   = array_diff($currentIds, $ids);
        // Detach removed
        if (!empty($toDetach)) {
            $this->detach($toDetach);
        }
        // Attach new
        if (!empty($toAttach)) {
            $this->attach($toAttach);
        }
        return $this;
    }

    /**
     * Toggle the attachment of models
     */
    public function toggle($ids)
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        $current = $this->newPivotQuery()
            ->where($this->foreignPivotKey, $this->parent->getKey())
            ->whereIn($this->relatedPivotKey, $ids)
            ->get();
        $currentIds = array_column($current, $this->relatedPivotKey);
        $toAttach   = array_diff($ids, $currentIds);
        $toDetach   = array_intersect($ids, $currentIds);
        if (!empty($toDetach)) {
            $this->detach($toDetach);
        }
        if (!empty($toAttach)) {
            $this->attach($toAttach);
        }
        return $this;
    }

    /**
     * Check if the relationship exists
     */
    public function exists()
    {
        return $this->count() > 0;
    }

    /**
     * Count related models
     */
    public function count()
    {
        $this->addJoin();
        return $this->query
            ->where($this->table . '.' . $this->foreignPivotKey, $this->parent->getKey())
            ->count();
    }

    /**
     * Pluck a single column from the relationship results
     */
    public function pluck($column)
    {
        $values  = [];
        $results = $this->getResults();
        foreach ($results as $result) {
            if (is_object($result) && isset($result->$column)) {
                $values[] = $result->$column;
            } elseif (is_array($result) && isset($result[$column])) {
                $values[] = $result[$column];
            }
        }
        return new class($values) {
            private $items;
            public function __construct($items)
            {
                $this->items = $items;
            }
            public function toArray()
            {
                return $this->items;
            }
            public function count()
            {
                return count($this->items);
            }
            public function isEmpty()
            {
                return empty($this->items);
            }
        };
    }

    public function getQuery()
    {
        $this->addJoin();
        return $this->query;
    }

    /**
     * Get pivot query builder
     */
    protected function newPivotQuery()
    {
        return $this->parent->db()->table($this->table);
    }

    /**
     * Attach a single model
     */
    protected function attachSingle($id, array $attributes = [])
    {
        $pivotData = array_merge([
            $this->foreignPivotKey => $this->parent->getKey(),
            $this->relatedPivotKey => $id
        ], $attributes);
        
        // Add timestamps only if not present and if they exist in pivot table
        // For this simple implementation, we'll only add created_at
        if (!isset($attributes['created_at'])) {
            $pivotData['created_at'] = date('Y-m-d H:i:s');
        }
        if (!isset($attributes['updated_at'])) {
            $pivotData['updated_at'] = date('Y-m-d H:i:s');
        }
        
        // Check if relationship already exists
        $exists = $this->newPivotQuery()
            ->where($this->foreignPivotKey, $this->parent->getKey())
            ->where($this->relatedPivotKey, $id)
            ->first();
        if (!$exists) {
            return $this->newPivotQuery()->insert($pivotData);
        }
        return false;
    }
}