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
    protected $relations = [];
    protected $exists = false;

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
        $this->syncOriginal();
        $this->fill($attributes);
        
        if (!isset($this->table)) {
            $this->table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->getClassBaseName($this))) . 's';
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

        $relation = $this->$key();

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
        $query = $this->newQuery();
        
        if ($this->exists) {
            $dirty = $this->getDirty();
            if (!empty($dirty)) {
                $query->where($this->primaryKey, $this->getKey())->update($dirty);
            }
        } else {
            $id = $query->insert($this->attributes);
            if ($id) {
                $this->setAttribute($this->primaryKey, $id);
                $this->exists = true;
            }
        }
        
        $this->syncOriginal();
        return $this;
    }

    /**
     * Find by primary key
     */
    public static function find($id)
    {
        $instance = new static;
        $result = $instance->newQuery()->where($instance->primaryKey, $id)->first();
        
        if ($result) {
            return $instance->newFromBuilder($result);
        }
        
        return null;
    }

    /**
     * Get all records
     */
    public static function all()
    {
        return static::query()->get();
    }

    /**
     * Handle dynamic static method calls into the query builder.
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->newQuery()->$method(...$parameters);
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

    // --- Helper methods ---

    public function newFromBuilder($attributes = [])
    {
        $model = new static;
        $model->fill((array) $attributes);
        $model->exists = true;
        $model->syncOriginal();
        return $model;
    }

    public function getKey()
    {
        return $this->getAttribute($this->primaryKey);
    }

    protected function getDirty()
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    protected function syncOriginal()
    {
        $this->original = $this->attributes;
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
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->getClassBaseName($this))) . '_id';
    }

    /**
     * Get the join table name for many-to-many relationships
     */
    protected function getJoinTableName($related)
    {
        $models = [
            strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->getClassBaseName($this))),
            strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->getClassBaseName($related)))
        ];
        
        sort($models);
        
        return implode('_', $models);
    }
}

// Example usage:
// class User extends Model {
//     protected $table = 'users';  // Optional if table name follows convention
//     public function profile() {
//         return $this->hasOne(Profile::class);
//     }
//     public function posts() {
//         return $this->hasMany(Post::class);
//     }
//     public function roles() {
//         return $this->belongsToMany(Role::class);
//     }
// }
// class Profile extends Model {
//     protected $table = 'profiles';
//     public function user() {
//         return $this->belongsTo(User::class);
//     }
// }
// class Post extends Model {
//     protected $table = 'posts';
//     public function user() {
//         return $this->belongsTo(User::class);
//     }
// }
// class Role extends Model {
//     protected $table = 'roles';
//     public function users() {
//         return $this->belongsToMany(User::class);
//     }
// }