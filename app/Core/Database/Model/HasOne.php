<?php

namespace Core\Database\Model;

use Core\Database\Model as DbModel;

class HasOne extends HasMany
{
    public function getResults()
    {
        $result = $this->query
            ->where($this->foreignKey, $this->parent->getData($this->localKey))
            ->first();
        return $result ? $this->related::newFromBuilder($result) : null;
    }

    public function match(array $models, array $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);
        foreach ($models as $model) {
            /** @var DbModel $model */
            $key = $model->getData($this->localKey);
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key][0]);
            }
        }
        return $models;
    }
}