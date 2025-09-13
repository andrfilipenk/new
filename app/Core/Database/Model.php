<?php
namespace Core\Database;

use Core\Di\Container;
use Core\Di\Injectable;
use Core\Database\Model\Relation;
use Core\Database\Model\HasOne;
use Core\Database\Model\HasMany;
use Core\Database\Model\BelongsTo;
use Core\Database\Model\BelongsToMany;

/**
 * Base Model class with relationship support
 */
abstract class Model
{
    use Injectable;

    protected $table;
    protected $primaryKey = 'id';
    protected $attributes = [];
    protected $original = [];
    protected $exists = false;
    protected $relations = [];

    // Relationship constants
    const HAS_ONE           = 'hasOne';
    const HAS_MANY          = 'hasMany';
    const BELONGS_TO        = 'belongsTo';
    const BELONGS_TO_MANY   = 'belongsToMany';

    /**
     * Model constructor
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
        
        if (!isset($this->table)) {
            // Auto-generate table name from class name
            $class = get_class($this);
            $class = substr(strrchr($class, '\\'), 1);
            $this->table = strtolower($class);
        }
    }

    /**
     * Returns db instance
     */
    static public function db(): Database {
        return Container::getDefault()->get('db');
    }

    /**
     * Get a new query instance for the model's table
     */
    public function newQuery()
    {
        return self::db()->table($this->table);
    }

    /**
     * Fill model with attributes
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    /**
     * Set attribute
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Get attribute
     */
    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        if (method_exists($this, $key)) {
            // Relationship method
            return $this->getRelationship($key);
        }

        return null;
    }

    /**
     * Get relationship
     */
    protected function getRelationship($key)
    {
        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        $method = $key;
        $relation = $this->$method();

        if (!$relation instanceof Relation) {
            return $relation;
        }

        return $this->relations[$key] = $relation->getResults();
    }

    /**
     * Magic getter
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter
     */
    public function __set($key, $value)
    {
        return $this->setAttribute($key, $value);
    }

    /**
     * Save model
     */
    public function save()
    {
        if ($this->exists) {
            // Update
            $primaryKey = $this->primaryKey;
            self::db()->table($this->table)
               ->where($primaryKey, $this->$primaryKey)
               ->update($this->attributes);
        } else {
            // Insert
            $id = self::db()->table($this->table)->insert($this->attributes);
            $this->primaryKey = $id;
            $this->exists = true;
        }
        
        return $this;
    }

    /**
     * Find by primary key
     */
    public static function find($id)
    {
        $instance = new static;

        
        $result = self::db()->table($instance->table)
                    ->where($instance->primaryKey, $id)
                    ->first();
        
        if ($result) {
            $instance->fill((array)$result);
            $instance->exists = true;
            return $instance;
        }
        
        return null;
    }

    /**
     * Get all records
     */
    public static function all()
    {
        $instance = new static;
        
        $results = self::db()->table($instance->table)->get();
        
        return array_map(function($item) use ($instance) {
            $model = new static;
            $model->fill((array)$item);
            $model->exists = true;
            return $model;
        }, $results);
    }

    /**
     * Define a one-to-one relationship
     */
    protected function hasOne($related, $foreignKey = null, $localKey = null)
    {
        $instance = new $related;
        
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->primaryKey;
        
        return new HasOne($instance, $this, $foreignKey, $localKey);
    }

    /**
     * Define a one-to-many relationship
     */
    protected function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $instance = new $related;
        
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->primaryKey;
        
        return new HasMany($instance, $this, $foreignKey, $localKey);
    }

    /**
     * Define an inverse relationship
     */
    protected function belongsTo($related, $foreignKey = null, $ownerKey = null)
    {
        $instance = new $related;
        
        $foreignKey = $foreignKey ?: $instance->getForeignKey();
        $ownerKey = $ownerKey ?: $instance->primaryKey;
        
        return new BelongsTo($instance, $this, $foreignKey, $ownerKey);
    }

    /**
     * Define a many-to-many relationship
     */
    protected function belongsToMany($related, $table = null, $foreignPivotKey = null, $relatedPivotKey = null)
    {
        $instance = new $related;
        
        $table = $table ?: $this->getJoinTableName($instance);
        $foreignPivotKey = $foreignPivotKey ?: $this->getForeignKey();
        $relatedPivotKey = $relatedPivotKey ?: $instance->getForeignKey();
        
        return new BelongsToMany($instance, $this, $table, $foreignPivotKey, $relatedPivotKey);
    }

    protected function getClassBaseName($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }

    /**
     * Get the foreign key for the model
     */
    protected function getForeignKey()
    {
        return strtolower($this->getClassBaseName($this)) . '_id';
    }

    /**
     * Get the join table name for many-to-many relationships
     */
    protected function getJoinTableName($related)
    {
        $models = [
            strtolower($this->getClassBaseName($this)),
            strtolower($this->getClassBaseName($related))
        ];
        
        sort($models);
        
        return implode('_', $models);
    }
}