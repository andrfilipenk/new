<?php
// app/_Core/Database/Model/BelongsTo.php
namespace Core\Database\Model;

use Core\Database\Model as DbModel;

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
        $foreignKeyValue = $this->parent->getData($this->foreignKey);
        $result = $foreignKeyValue ? $this->query->where($this->ownerKey, $foreignKeyValue)->first() : null;
        return $result ? $this->related::newFromBuilder($result) : null;
    }

    public function addEagerConstraints(array $models)
    {
        $keys = array_unique(array_filter(array_map(function ($model) {
            return $model->getData($this->foreignKey);
        }, $models)));
        $this->query->whereIn($this->ownerKey, $keys);
    }

    public function match(array $models, array $results, $relation)
    {
        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->getData($this->ownerKey)] = $result;
        }
        foreach ($models as $model) {
            $key = $model->getData($this->foreignKey);
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            }
        }
        return $models;
    }
}