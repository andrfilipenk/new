<?php
// app/Core/Database/Model/BelongsToMany.php
namespace Core\Database\Model;

use Core\Database\Model as DbModel;

class BelongsToMany extends Relation
{
    protected $table;
    protected $foreignPivotKey;
    protected $relatedPivotKey;
    
    public function __construct(DbModel $related, DbModel $parent, $table, $foreignPivotKey, $relatedPivotKey)
    {
        parent::__construct($related, $parent);
        $this->table = $table;
        $this->foreignPivotKey = $foreignPivotKey;
        $this->relatedPivotKey = $relatedPivotKey;
    }
    
    public function getResults()
    {
        $this->addJoin();
        return $this->query
            ->where($this->table . '.' . $this->foreignPivotKey, $this->parent->getKey())
            ->get();
    }

    public function addEagerConstraints(array $models)
    {
        $this->query->whereIn($this->table . '.' . $this->foreignPivotKey, $this->getKeys($models, $this->parent->getKeyName()));
    }

    public function match(array $models, array $results, $relation)
    {
        $dictionary = [];
        foreach ($results as $result) {
            $pivotKey = $result->pivot->{$this->foreignPivotKey};
            $dictionary[$pivotKey][] = $result;
        }
        foreach ($models as $model) {
            $key = $model->getKey();
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            }
        }
        return $models;
    }

    protected function addJoin()
    {
        $this->query->select($this->related->getTable() . '.*', $this->table . '.* as pivot')
            ->join(
                $this->table, 
                $this->table . '.' . $this->relatedPivotKey, 
                '=', 
                $this->related->getTable() . '.' . $this->related->getKeyName()
            );
    }

    protected function getKeys(array $models, $key)
    {
        return array_unique(array: array_filter(array_map(function ($model) use ($key) {
            return $model->getData($key);
        }, $models)));
    }
}