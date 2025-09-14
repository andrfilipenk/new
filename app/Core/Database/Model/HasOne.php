<?php
namespace Core\Database\Model;

use Core\Database\Model as DbModel;

/**
 * HasOne Relationship
 */
class HasOne extends HasMany
{
    public function getResults()
    {
        return $this->query
            ->where($this->foreignKey, $this->parent->getAttribute($this->localKey))
            ->first();
    }

    public function match(array $models, array $results, $relation)
    {
        $dictionary = $this->buildDictionary($results);

        foreach ($models as $model) {
            $key = $model->getAttribute($this->localKey);
            if (isset($dictionary[$key])) {
                $model->setRelation($relation, $dictionary[$key][0]);
            }
        }

        return $models;
    }
}