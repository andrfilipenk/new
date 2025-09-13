<?php
namespace Core\Database\Model;

use Core\Database\Model as DbModel;

/**
 * HasMany Relationship
 */
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
        return $this->query
            ->where($this->foreignKey, $this->parent->{$this->localKey})
            ->get();
    }
}