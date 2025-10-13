<?php
// app/Core/Database/Model/Relation.php
namespace Core\Database\Model;

use Core\Database\Model as DbModel;

abstract class Relation
{
    protected $related;
    protected $parent;
    protected $query;
    
    public function __construct(DbModel $related, DbModel $parent)
    {
        $this->related  = $related;
        $this->parent   = $parent;
        $this->query    = $related->newQuery();
    }
    
    abstract public function getResults();

    /**
     * Set the constraints for an eager load of the relation.
     *
     * @param  DbModel[]  $models
     * @return void
     */
    abstract public function addEagerConstraints(array $models);

    /**
     * Match the eagerly loaded results to their parents.
     *
     * @param  DbModel[]   $models
     * @param  array   $results
     * @param  DbModel[]  $relation
     * @return array
     */
    abstract public function match(array $models, array $results, $relation);
    
    public function getQuery()
    {
        return $this->query;
    }

    public function getRelatedInstance()
    {
        return $this->related;
    }
}