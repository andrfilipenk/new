<?php
namespace Core\Database\Model;

use Core\Database\Model as DbModel;

/**
 * Base Relation class
 */
abstract class Relation
{
    protected $related;
    protected $parent;
    protected $query;
    
    public function __construct(DbModel $related, DbModel $parent)
    {
        $this->related = $related;
        $this->parent = $parent;
        $this->query = $related->newQuery();
    }
    
    abstract public function getResults();
    
    public function getQuery()
    {
        return $this->query;
    }
}