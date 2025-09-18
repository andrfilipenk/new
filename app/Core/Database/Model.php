<?php
// app/Core/Database/Model.php
namespace Core\Database;

use Core\Di\Container;
use Core\Database\Model\HasOne;
use Core\Database\Model\HasMany;
use Core\Database\Model\BelongsTo;
use Core\Database\Model\BelongsToMany;

abstract class Model
{
    protected $table = '';
    protected $primaryKey = 'id';
    protected array $attributes = [];
    protected array $original = [];
    protected array $relations = [];
    protected bool $exists = false;
    protected array $with = [];
    protected array $fillable = [];
    protected array $guarded = [];
    protected array $hidden = [];
    protected array $casts = [];
    protected array $dates = [];
    protected bool $softDeletes = false;
    protected string $deletedAt = 'deleted_at';
    
    private static array $instances = [];

    public function __construct(array $attributes = [])
    {
        if (!$this->table) {
            $this->table = $this->getTableName();
        }
        $this->fill($attributes);
        $this->syncOriginal();
    }

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


    public static function query(): Database
    {
        $instance = static::getInstance();
        return self::db()->table($instance->table);
    }

    public static function find(mixed $id): Model|null
    {
        if ($id === null) return null;
        if (!empty(static::getInstance()->with)) {
            return static::with(static::getInstance()->with)->where(static::getInstance()->primaryKey, $id)->first();
        }
        $result = static::query()->where(static::getInstance()->primaryKey, $id)->first();
        return $result ? static::newFromBuilder($result) : null;
    }

    public static function findMany(array $ids): array
    {
        if (empty($ids)) return [];
        
        $results = static::query()->whereIn(static::getInstance()->primaryKey, $ids)->get();
        return array_map([static::class, 'newFromBuilder'], $results);
    }

    public static function all(): array
    {
        if (!empty(static::getInstance()->with)) {
            return static::with(static::getInstance()->with)->get();
        }
        $results = static::query()->get();
        return array_map([static::class, 'newFromBuilder'], $results);
    }

    public static function with(array $relations): QueryBuilder
    {
        return new QueryBuilder(static::class, $relations);
    }

    public function save(): bool
    {
        // Fire before save events
        $this->fireEvent('saving');
        
        if ($this->exists) {
            $this->fireEvent('updating');
            $dirty = $this->getDirty();
            if (!empty($dirty)) {
                // Add updated_at timestamp
                if ($this->usesTimestamps() && !isset($dirty['updated_at'])) {
                    $dirty['updated_at'] = date('Y-m-d H:i:s');
                    $this->setData('updated_at', $dirty['updated_at']);
                }
                
                $affected = static::query()->where($this->primaryKey, $this->getKey())->update($dirty);
                if ($affected > 0) {
                    $this->syncOriginal();
                    $this->fireEvent('updated');
                    $this->fireEvent('saved');
                    return true;
                }
            }
            return true; // No changes to save
        } else {
            $this->fireEvent('creating');
            
            // Add timestamps
            if ($this->usesTimestamps()) {
                $now = date('Y-m-d H:i:s');
                if (!isset($this->attributes['created_at'])) {
                    $this->setData('created_at', $now);
                }
                if (!isset($this->attributes['updated_at'])) {
                    $this->setData('updated_at', $now);
                }
            }
            
            $id = static::query()->insert($this->attributes);
            if ($id) {
                $this->setData($this->primaryKey, $id);
                $this->exists = true;
                $this->syncOriginal();
                $this->fireEvent('created');
                $this->fireEvent('saved');
                return true;
            }
        }

        return false;
    }

    /**
     * Delete the model
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        
        $this->fireEvent('deleting');
        
        if ($this->softDeletes) {
            // Soft delete
            $this->setData($this->deletedAt, date('Y-m-d H:i:s'));
            $result = $this->save();
        } else {
            // Hard delete
            $result = static::query()->where($this->primaryKey, $this->getKey())->delete() > 0;
            if ($result) {
                $this->exists = false;
            }
        }
        
        if ($result) {
            $this->fireEvent('deleted');
        }
        
        return $result;
    }

    /**
     * Restore a soft-deleted model
     */
    public function restore(): bool
    {
        if (!$this->softDeletes) {
            return false;
        }
        
        $this->setData($this->deletedAt, null);
        return $this->save();
    }

    /**
     * Force delete (hard delete) a model
     */
    public function forceDelete(): bool
    {
        $this->fireEvent('deleting');
        
        $result = static::query()->where($this->primaryKey, $this->getKey())->delete() > 0;
        
        if ($result) {
            $this->exists = false;
            $this->fireEvent('deleted');
        }
        
        return $result;
    }

    // Relationship methods - Fixed to work with Relations
    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        return new HasOne(
            $this->getRelatedInstance($related),
            $this,
            $foreignKey ?? $this->getForeignKey(), 
            $localKey ?? $this->primaryKey
        );
    }

    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        return new HasMany(
            $this->getRelatedInstance($related),
            $this,
            $foreignKey ?? $this->getForeignKey(),
            $localKey ?? $this->primaryKey
        );
    }

    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        $instance = $this->getRelatedInstance($related);
        return new BelongsTo(
            $instance,
            $this,
            $foreignKey ?? $instance->getForeignKey(),
            $ownerKey ?? $instance->primaryKey
        );
    }

    public function belongsToMany(string $related, ?string $table = null, ?string $foreignPivotKey = null, ?string $relatedPivotKey = null): BelongsToMany
    {
        $instance = $this->getRelatedInstance($related);
        return new BelongsToMany(
            $instance,
            $this,
            $table ?? $this->getJoinTableName($instance),
            $foreignPivotKey ?? $this->getForeignKey(),
            $relatedPivotKey ?? $instance->getForeignKey()
        );
    }

    // Helper methods
    private static function getInstance(): static
    {
        $class = static::class;
        return self::$instances[$class] ??= new static;
    }

    private function getRelatedInstance(string $class): Model
    {
        return self::$instances[$class] ??= new $class;
    }

    private function getTableName(): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', self::className(static::class))) . 's';
    }

    public static function newFromBuilder(array $attributes): static
    {
        $model = new static;
        $model->attributes = $attributes;
        $model->exists = true;
        $model->syncOriginal();
        return $model;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    // Accessors and mutators
    public function fill(array $attributes): self
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return $this;
    }

    public function setData(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function getData($key = null): mixed
    {
        if ($key === null) {
            return $this->attributes;
        }
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
        if (method_exists($this, $key) && !array_key_exists($key, $this->relations)) {
            return $this->relations[$key] = $this->$key()->getResults();
        }
        return $this->relations[$key] ?? null;
    }

    public function getKey(): mixed
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    public function setRelation(string $key, mixed $value): void
    {
        $this->relations[$key] = $value;
    }

    protected function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    private function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }

    private function getForeignKey(): string
    {
        return strtolower(self::className(static::class)) . '_id';
    }

    private function getJoinTableName(Model $related): string
    {
        $models = [
            strtolower(self::className(static::class)),
            strtolower(self::className($related))
        ];
        sort($models);
        return implode('_', $models);
    }

    public static function className($class): string
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }

    // Magic methods
    public function __get(string $key): mixed
    {
        return $this->getData($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setData($key, $value);
    }

    public static function __callStatic(string $method, array $parameters): mixed
    {
        return static::query()->$method(...$parameters);
    }

    /**
     * Check if model uses timestamps
     */
    protected function usesTimestamps(): bool
    {
        return in_array('created_at', array_keys($this->attributes)) || 
               in_array('updated_at', array_keys($this->attributes)) ||
               property_exists($this, 'timestamps') && $this->timestamps !== false;
    }

    /**
     * Fire model events
     */
    protected function fireEvent(string $event): void
    {
        $eventManager = Container::getDefault()->get('eventsManager');
        if ($eventManager) {
            $eventManager->trigger('model:' . $event, $this);
        }
    }

    /**
     * Get a query builder with global scopes applied
     */
    public static function queryWithScopes(): Database
    {
        $instance = static::getInstance();
        $query = self::db()->table($instance->table);
        
        // Apply soft delete scope
        if ($instance->softDeletes) {
            $query->where($instance->deletedAt, null);
        }
        
        return $query;
    }

    /**
     * Query including soft deleted records
     */
    public static function withTrashed(): Database
    {
        $instance = static::getInstance();
        return self::db()->table($instance->table);
    }

    /**
     * Query only soft deleted records
     */
    public static function onlyTrashed(): Database
    {
        $instance = static::getInstance();
        return self::db()->table($instance->table)->where($instance->deletedAt, '!=', null);
    }

    /**
     * Cast attributes to their proper types
     */
    protected function castAttribute(string $key, $value)
    {
        if (!isset($this->casts[$key])) {
            return $value;
        }
        
        $castType = $this->casts[$key];
        
        return match($castType) {
            'int', 'integer' => (int) $value,
            'real', 'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'array', 'json' => is_string($value) ? json_decode($value, true) : $value,
            'date', 'datetime' => $value instanceof \DateTime ? $value : new \DateTime($value),
            default => $value
        };
    }

    /**
     * Check if an attribute is fillable
     */
    protected function isFillable(string $key): bool
    {
        if (!empty($this->fillable)) {
            return in_array($key, $this->fillable);
        }
        
        if (!empty($this->guarded)) {
            return !in_array($key, $this->guarded);
        }
        
        return true;
    }
}
