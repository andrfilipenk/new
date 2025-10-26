<?php

namespace Core\ORM;

use Core\Database\DB;
use Core\Database\QueryBuilder;
use Core\Database\Relations\HasOne;
use Core\Database\Relations\HasMany;
use Core\Database\Relations\BelongsTo;
use Core\Database\Relations\BelongsToMany;
use Core\Database\Relations\MorphOne;
use Core\Database\Relations\MorphMany;
use Core\Database\Relations\MorphTo;

/**
 * Base Model Class
 * 
 * Active Record pattern implementation with CRUD operations
 */
abstract class Model
{
    /**
     * @var string The database table name
     */
    protected string $table;
    
    /**
     * @var string Primary key column
     */
    protected string $primaryKey = 'id';
    
    /**
     * @var bool Enable timestamps
     */
    protected bool $timestamps = true;
    
    /**
     * @var bool Enable soft deletes
     */
    protected bool $softDeletes = false;
    
    /**
     * @var array Fillable columns
     */
    protected array $fillable = [];
    
    /**
     * @var array Guarded columns
     */
    protected array $guarded = ['id'];
    
    /**
     * @var array Hidden columns (not included in toArray)
     */
    protected array $hidden = [];
    
    /**
     * @var array Casted attributes
     */
    protected array $casts = [];
    
    /**
     * @var array Model attributes
     */
    protected array $attributes = [];
    
    /**
     * @var array Original attributes
     */
    protected array $original = [];
    
    /**
     * @var array Loaded relationships
     */
    protected array $relations = [];
    
    /**
     * @var bool Indicates if model exists in database
     */
    protected bool $exists = false;
    
    /**
     * Constructor
     */
    public function __construct(array $attributes = [])
    {
        // Set table name from class name if not defined
        if (!isset($this->table)) {
            $this->table = $this->getTableName();
        }
        
        $this->fill($attributes);
    }
    
    /**
     * Get table name from class name
     */
    protected function getTableName(): string
    {
        $className = (new \ReflectionClass($this))->getShortName();
        // Convert PascalCase to snake_case and pluralize
        $snake = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className));
        return $snake . 's';
    }
    
    /**
     * Fill model with attributes
     */
    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        
        return $this;
    }
    
    /**
     * Check if attribute is fillable
     */
    protected function isFillable(string $key): bool
    {
        // If fillable is empty, check guarded
        if (empty($this->fillable)) {
            return !in_array($key, $this->guarded);
        }
        
        return in_array($key, $this->fillable);
    }
    
    /**
     * Set attribute value
     */
    public function setAttribute(string $key, $value): void
    {
        // Apply mutator if exists
        $method = 'set' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
        
        if (method_exists($this, $method)) {
            $this->attributes[$key] = $this->$method($value);
        } else {
            $this->attributes[$key] = $value;
        }
    }
    
    /**
     * Get attribute value
     */
    public function getAttribute(string $key)
    {
        // Check if it's a relationship
        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }
        
        // Try to load relationship if method exists
        if (method_exists($this, $key)) {
            $relation = $this->$key();
            if ($relation instanceof \Core\Database\Relations\Relation) {
                $this->relations[$key] = $relation->get();
                return $this->relations[$key];
            }
        }
        
        // Get raw attribute
        $value = $this->attributes[$key] ?? null;
        
        // Apply accessor if exists
        $method = 'get' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
        
        if (method_exists($this, $method)) {
            return $this->$method($value);
        }
        
        // Apply casts
        if (array_key_exists($key, $this->casts)) {
            return $this->castAttribute($key, $value);
        }
        
        return $value;
    }
    
    /**
     * Cast attribute to specified type
     */
    protected function castAttribute(string $key, $value)
    {
        if ($value === null) {
            return null;
        }
        
        $castType = $this->casts[$key];
        
        return match($castType) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'array', 'json' => is_string($value) ? json_decode($value, true) : $value,
            'object' => is_string($value) ? json_decode($value) : $value,
            'datetime' => new \DateTime($value),
            default => $value,
        };
    }
    
    /**
     * Magic getter
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }
    
    /**
     * Magic setter
     */
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }
    
    /**
     * Magic isset
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]) || isset($this->relations[$key]);
    }
    
    /**
     * Get query builder for this model
     */
    public static function query(): QueryBuilder
    {
        $instance = new static;
        return DB::table($instance->table);
    }
    
    /**
     * Find by primary key
     */
    public static function find($id): ?static
    {
        $instance = new static;
        
        $row = static::query()
            ->where($instance->primaryKey, '=', $id)
            ->first();
        
        if (!$row) {
            return null;
        }
        
        return static::hydrate($row);
    }
    
    /**
     * Find or fail
     */
    public static function findOrFail($id): static
    {
        $model = static::find($id);
        
        if (!$model) {
            throw new \Exception("Model not found with ID: {$id}");
        }
        
        return $model;
    }
    
    /**
     * Get all records
     */
    public static function all(): array
    {
        $rows = static::query()->get();
        
        return array_map(function($row) {
            return static::hydrate($row);
        }, $rows);
    }
    
    /**
     * Create and save a new model
     */
    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();
        
        return $model;
    }
    
    /**
     * Save the model
     */
    public function save(): bool
    {
        // Add timestamps
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            
            if (!$this->exists) {
                $this->attributes['created_at'] = $now;
            }
            
            $this->attributes['updated_at'] = $now;
        }
        
        if ($this->exists) {
            return $this->performUpdate();
        }
        
        return $this->performInsert();
    }
    
    /**
     * Perform insert
     */
    protected function performInsert(): bool
    {
        $id = static::query()->insert($this->attributes);
        
        if ($id) {
            $this->attributes[$this->primaryKey] = $id;
            $this->exists = true;
            $this->syncOriginal();
            return true;
        }
        
        return false;
    }
    
    /**
     * Perform update
     */
    protected function performUpdate(): bool
    {
        // Get only dirty attributes
        $dirty = $this->getDirty();
        
        if (empty($dirty)) {
            return true;
        }
        
        $affected = static::query()
            ->where($this->primaryKey, '=', $this->attributes[$this->primaryKey])
            ->update($dirty);
        
        if ($affected > 0) {
            $this->syncOriginal();
            return true;
        }
        
        return false;
    }
    
    /**
     * Update the model
     */
    public function update(array $attributes): bool
    {
        $this->fill($attributes);
        return $this->save();
    }
    
    /**
     * Delete the model
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        
        if ($this->softDeletes) {
            $this->attributes['deleted_at'] = date('Y-m-d H:i:s');
            return $this->save();
        }
        
        $affected = static::query()
            ->where($this->primaryKey, '=', $this->attributes[$this->primaryKey])
            ->delete();
        
        if ($affected > 0) {
            $this->exists = false;
            return true;
        }
        
        return false;
    }
    
    /**
     * Force delete (even if soft deletes enabled)
     */
    public function forceDelete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        
        $affected = static::query()
            ->where($this->primaryKey, '=', $this->attributes[$this->primaryKey])
            ->delete();
        
        if ($affected > 0) {
            $this->exists = false;
            return true;
        }
        
        return false;
    }
    
    /**
     * Get dirty attributes
     */
    protected function getDirty(): array
    {
        $dirty = [];
        
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        
        return $dirty;
    }
    
    /**
     * Sync original attributes
     */
    protected function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }
    
    /**
     * Hydrate model from database row
     */
    public static function hydrate(array $row): static
    {
        $model = new static;
        $model->attributes = $row;
        $model->original = $row;
        $model->exists = true;
        
        return $model;
    }
    
    /**
     * Convert model to array
     */
    public function toArray(): array
    {
        $array = $this->attributes;
        
        // Remove hidden attributes
        foreach ($this->hidden as $key) {
            unset($array[$key]);
        }
        
        // Add relations
        foreach ($this->relations as $key => $value) {
            if (is_array($value)) {
                $array[$key] = array_map(function($model) {
                    return $model instanceof Model ? $model->toArray() : $model;
                }, $value);
            } elseif ($value instanceof Model) {
                $array[$key] = $value->toArray();
            } else {
                $array[$key] = $value;
            }
        }
        
        return $array;
    }
    
    /**
     * Convert model to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
    
    /**
     * Fresh instance from database
     */
    public function fresh(): ?static
    {
        if (!$this->exists) {
            return null;
        }
        
        return static::find($this->attributes[$this->primaryKey]);
    }
    
    /**
     * Refresh model from database
     */
    public function refresh(): static
    {
        if (!$this->exists) {
            throw new \Exception("Cannot refresh non-existent model");
        }
        
        $fresh = $this->fresh();
        
        if ($fresh) {
            $this->attributes = $fresh->attributes;
            $this->original = $fresh->original;
            $this->relations = [];
        }
        
        return $this;
    }
    
    // ==================== Relationship Methods ====================
    
    /**
     * Define a one-to-one relationship
     */
    protected function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        return new HasOne($this, $related, $foreignKey, $localKey);
    }
    
    /**
     * Define a one-to-many relationship
     */
    protected function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        return new HasMany($this, $related, $foreignKey, $localKey);
    }
    
    /**
     * Define an inverse one-to-one or one-to-many relationship
     */
    protected function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        return new BelongsTo($this, $related, $foreignKey, $ownerKey);
    }
    
    /**
     * Define a many-to-many relationship
     */
    protected function belongsToMany(
        string $related,
        ?string $table = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null,
        ?string $parentKey = null,
        ?string $relatedKey = null
    ): BelongsToMany {
        return new BelongsToMany($this, $related, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey);
    }
    
    /**
     * Define a polymorphic one-to-one relationship
     */
    protected function morphOne(string $related, string $name, ?string $localKey = null): MorphOne
    {
        return new MorphOne($this, $related, $name, $localKey);
    }
    
    /**
     * Define a polymorphic one-to-many relationship
     */
    protected function morphMany(string $related, string $name, ?string $localKey = null): MorphMany
    {
        return new MorphMany($this, $related, $name, $localKey);
    }
    
    /**
     * Define a polymorphic inverse relationship
     */
    protected function morphTo(string $name): MorphTo
    {
        return new MorphTo($this, $name);
    }
    
    /**
     * Set a given relationship on the model
     */
    public function setRelation(string $relation, $value): static
    {
        $this->relations[$relation] = $value;
        return $this;
    }
    
    /**
     * Eager load relationships
     */
    public static function with(string|array $relations): QueryBuilder
    {
        $instance = new static;
        $query = static::query();
        
        // Store relationships to load
        $query->eagerLoad = is_array($relations) ? $relations : [$relations];
        
        return $query;
    }
    
    /**
     * Get the table name
     */
    public function getTable(): string
    {
        return $this->table;
    }
    
    /**
     * Check if model exists in database
     */
    public function exists(): bool
    {
        return $this->exists;
    }
    
    /**
     * Create a new query builder instance
     */
    public function newQuery(): QueryBuilder
    {
        return DB::table($this->table);
    }
    
    /**
     * Handle dynamic static method calls into the query builder
     * This allows calling Model::where(), Model::orderBy(), etc.
     */
    public static function __callStatic(string $method, array $parameters)
    {
        return (new static)->newQuery()->$method(...$parameters);
    }
}
