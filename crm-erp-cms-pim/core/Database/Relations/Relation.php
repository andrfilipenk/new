<?php

namespace Core\Database\Relations;

use Core\Database\Model;
use Core\Database\QueryBuilder;

/**
 * Base Relation Class
 */
abstract class Relation
{
    protected Model $parent;
    protected string $related;
    protected QueryBuilder $query;

    public function __construct(Model $parent, string $related)
    {
        $this->parent = $parent;
        $this->related = $related;
        $this->query = (new $related)->newQuery();
    }

    /**
     * Get the results of the relationship
     */
    abstract public function get();

    /**
     * Get the first result of the relationship
     */
    abstract public function first();

    /**
     * Add constraints to the query
     */
    abstract public function addConstraints(): void;

    /**
     * Add eager loading constraints
     */
    abstract public function addEagerConstraints(array $models): void;

    /**
     * Match the eagerly loaded results to their parents
     */
    abstract public function match(array $models, array $results, string $relation): array;

    /**
     * Get the query builder
     */
    public function getQuery(): QueryBuilder
    {
        return $this->query;
    }

    /**
     * Forward method calls to the query builder
     */
    public function __call(string $method, array $parameters)
    {
        $result = $this->query->$method(...$parameters);
        
        if ($result === $this->query) {
            return $this;
        }
        
        return $result;
    }
}
