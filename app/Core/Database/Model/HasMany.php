<?php

namespace Core\Database\Model;

use Core\Database\Model as DbModel;

class HasMany extends Relation
{
    protected $foreignKey;
    protected $localKey;

    public function __construct(DbModel $related, DbModel $parent, $foreignKey, $localKey)
    {
        parent::__construct($related, $parent);
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    public function getResults()
    {
        $results = $this->query
            ->where($this->foreignKey, $this->parent->getData($this->localKey))
            ->get();
        return array_map(fn($row) => $this->related::newFromBuilder($row), $results);
    }

    public function addEagerConstraints(array $models)
    {
        $this->query->whereIn($this->foreignKey, $this->getKeys($models, $this->localKey));
    }

    public function match(array $models, array $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);
        foreach ($models as $model) {
            $key = $model->getData($this->localKey);
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key]);
            }
        }
        return $models;
    }

    protected function buildDictionary(array $results)
    {
        $dictionary = [];
        foreach ($results as $result) {
            $dictionary[$result->getData($this->foreignKey)][] = $result;
        }
        return $dictionary;
    }

    protected function getKeys(array $models, $key)
    {
        return array_unique(array_filter(array_map(function ($model) use ($key) {
            return $model->getData($key);
        }, $models)));
    }
}