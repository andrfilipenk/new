<?php
namespace Core\Database\Model;

use Core\Database\Model as DbModel;

/**
 * BelongsTo Relationship
 */
class BelongsTo extends Relation
{
    protected $foreignKey;
    protected $ownerKey;
    
    public function __construct(DbModel $related, DbModel $parent, $foreignKey, $ownerKey)
    {
        parent::__construct($related, $parent);
        
        $this->foreignKey = $foreignKey;
        $this->ownerKey = $ownerKey;
    }
    
    public function getResults()
    {
        $foreignKeyValue = $this->parent->getAttribute($this->foreignKey);
        return $foreignKeyValue ? $this->query->where($this->ownerKey, $foreignKeyValue)->first() : null;
    }

    public function addEagerConstraints(array $models)
    {
        $keys = array_unique(array_filter(array_map(function ($model) {
            return $model->getAttribute($this->foreignKey);
        }, $models)));

        $this->query->whereIn($this->ownerKey, $keys);
    }

    public function match(array $models, array $results, $relation)
    {
        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->getAttribute($this->ownerKey)] = $result;
        }

        foreach ($models as $model) {
            $key = $model->getAttribute($this->foreignKey);
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            }
        }

        return $models;
    }
}



