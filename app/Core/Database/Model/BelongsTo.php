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
        return $this->query
            ->where($this->ownerKey, $this->parent->{$this->foreignKey})
            ->first();
    }
}



