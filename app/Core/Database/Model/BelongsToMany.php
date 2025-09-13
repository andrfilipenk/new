<?php
namespace Core\Database\Model;

use Core\Database\Model as DbModel;

/**
 * BelongsToMany Relationship
 */
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
        // Join the pivot table with the related table
        $results = $this->query
            ->select($this->related->table . '.*')
            ->join($this->table, 
                $this->table . '.' . $this->relatedPivotKey, 
                '=', 
                $this->related->table . '.' . $this->related->primaryKey
            )
            ->where($this->table . '.' . $this->foreignPivotKey, $this->parent->{$this->parent->primaryKey})
            ->get();
            
        return array_map(function($item) {
            $model = new $this->related;
            $model->fill((array)$item);
            $model->exists = true;
            return $model;
        }, $results);
    }
}