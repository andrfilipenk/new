<?php
namespace Core\Database;

use Core\Di\Container;
use Core\Database\Model\Relation;
use Core\Database\Model\HasOne;
use Core\Database\Model\HasMany;
use Core\Database\Model\BelongsTo;
use Core\Database\Model\BelongsToMany;

abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected array $attributes = [];
    protected array $original = [];
    protected array $relations = [];
    protected bool $exists = false;
    
    private static array $instances = [];
    private static Database $db;

    public function __construct(array $attributes = [])
    {
        if (!isset(self::$db)) {
            self::$db = Container::getDefault()->get('db');
        }

        if (!$this->table) {
            $this->table = $this->getTableName();
        }

        $this->fill($attributes);
        $this->syncOriginal();
    }

    public static function query(): Database
    {
        $instance = static::getInstance();
        return self::$db->table($instance->table);
    }

    public static function find(mixed $id): ?static
    {
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
        return array_map([static::class, 'newFromBuilder'], static::query()->get());
    }

    public static function with(array $relations): QueryBuilder
    {
        return new QueryBuilder(static::class, $relations);
    }

    public function save(): bool
    {
        if ($this->exists) {
            $dirty = $this->getDirty();
            if (!empty($dirty)) {
                static::query()->where($this->primaryKey, $this->getKey())->update($dirty);
            }
        } else {
            $id = static::query()->insert($this->attributes);
            $this->setAttribute($this->primaryKey, $id);
            $this->exists = true;
        }

        $this->syncOriginal();
        return true;
    }

    // Relationship methods
    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        return new HasOne($this->getRelatedInstance($related), $this, 
            $foreignKey ?? $this->getForeignKey(), 
            $localKey ?? $this->primaryKey
        );
    }

    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        return new HasMany($this->getRelatedInstance($related), $this,
            $foreignKey ?? $this->getForeignKey(),
            $localKey ?? $this->primaryKey
        );
    }

    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        $instance = $this->getRelatedInstance($related);
        return new BelongsTo($instance, $this,
            $foreignKey ?? $instance->getForeignKey(),
            $ownerKey ?? $instance->primaryKey
        );
    }

    public function belongsToMany(string $related, ?string $table = null, ?string $foreignPivotKey = null, ?string $relatedPivotKey = null): BelongsToMany
    {
        $instance = $this->getRelatedInstance($related);
        return new BelongsToMany($instance, $this,
            $table ?? $this->getJoinTableName($instance),
            $foreignPivotKey ?? $this->getForeignKey(),
            $relatedPivotKey ?? $instance->getForeignKey()
        );
    }

    // Optimized helper methods
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
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', class_basename(static::class))) . 's';
    }

    public static function newFromBuilder(array $attributes): static
    {
        $model = new static;
        $model->attributes = $attributes;
        $model->exists = true;
        $model->syncOriginal();
        return $model;
    }

    // Accessors
    public function fill(array $attributes): self
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return $this;
    }

    public function setAttribute(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function getAttribute(string $key): mixed
    {
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

    private function getDirty(): array
    {
        return array_filter($this->attributes, fn($value, $key) => 
            !array_key_exists($key, $this->original) || $value !== $this->original[$key], 
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }

    private function getForeignKey(): string
    {
        return strtolower(class_basename(static::class)) . '_id';
    }

    private function getJoinTableName(Model $related): string
    {
        $models = [
            strtolower(class_basename(static::class)),
            strtolower(class_basename($related))
        ];
        sort($models);
        return implode('_', $models);
    }

    // Magic methods
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public static function __callStatic(string $method, array $parameters): mixed
    {
        return static::query()->$method(...$parameters);
    }
}

/*
// Eager loading (prevents N+1)
$users = User::with(['posts', 'profile'])->get();

// Optimized bulk operations
$users = User::findMany([1, 2, 3, 4, 5]);

// Fluent API remains the same
$activeUsers = User::where('status', 'active')
                  ->orderBy('created_at', 'desc')
                  ->limit(10)
                  ->get();
                  */