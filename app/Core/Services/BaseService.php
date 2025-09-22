<?php
// app/Core/Services/BaseService.php
namespace Core\Services;

use Core\Database\Model;
use Core\Validation\Validator;
use Core\Di\Injectable;

/**
 * Base service class for business logic separation
 * Following super-senior PHP practices with service layer pattern
 */
abstract class BaseService
{
    use Injectable;

    /** @var string The model class this service handles */
    protected string $modelClass;
    
    /** @var array Default validation rules */
    protected array $validationRules = [];
    
    /** @var array Relations to eager load by default */
    protected array $defaultWith = [];

    public function __construct()
    {
        if (!$this->modelClass) {
            throw new \Exception("Model class must be defined in " . static::class);
        }
    }

    /**
     * Get all records with optional eager loading
     */
    public function getAll(array $with = []): array
    {
        $with = array_merge($this->defaultWith, $with);
        if (!empty($with)) {
            return $this->modelClass::with($with)->get();
        }
        return $this->modelClass::all();
    }

    /**
     * Find a record by ID
     */
    public function find($id): ?Model
    {
        if (!empty($this->defaultWith)) {
            return $this->modelClass::with($this->defaultWith)
                ->where($this->getModelInstance()->getKeyName(), $id)
                ->first();
        }
        return $this->modelClass::find($id);
    }

    /**
     * Create a new record with validation
     */
    public function create(array $data): Model
    {
        $this->validateData($data);
        $this->beforeCreate($data);
        $record = new $this->modelClass($data);
        if (!$record->save()) {
            throw new \Exception("Failed to create record");
        }
        $this->afterCreate($record, $data);
        return $record;
    }

    /**
     * Update an existing record with validation
     */
    public function update(Model $record, array $data): Model
    {
        $this->validateData($data, $record->getKey());
        $this->beforeUpdate($record, $data);
        $record->fill($data);
        if (!$record->save()) {
            throw new \Exception("Failed to update record");
        }
        $this->afterUpdate($record, $data);
        return $record;
    }

    /**
     * Delete a record
     */
    public function delete(Model $record): bool
    {
        $this->beforeDelete($record);
        $result = $record->delete();
        if ($result) {
            $this->afterDelete($record);
        }
        return $result;
    }

    /**
     * Restore a soft-deleted record
     */
    public function restore(Model $record): bool
    {
        if (!method_exists($record, 'restore')) {
            throw new \Exception("Model does not support soft deletes");
        }
        return $record->restore();
    }

    /**
     * Get paginated results
     */
    public function paginate(int $page = 1, int $perPage = 15, array $with = []): array
    {
        $with = array_merge($this->defaultWith, $with);
        $offset = ($page - 1) * $perPage;
        $query = !empty($with) ? 
            $this->modelClass::with($with) : 
            $this->modelClass::query();
        return $query->limit($perPage)->offset($offset)->get();
    }

    /**
     * Search records with filters
     */
    public function search(array $filters = [], array $with = []): array
    {
        $with = array_merge($this->defaultWith, $with);
        $query = !empty($with) ? 
            $this->modelClass::with($with) : 
            $this->modelClass::query();
        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                $query->where($field, $value);
            }
        }
        return $query->get();
    }

    /**
     * Validate data using defined rules
     */
    protected function validateData(array $data, $excludeId = null): void
    {
        if (empty($this->validationRules)) {
            return;
        }
        // Add ID to unique rules for updates
        $rules = $this->validationRules;
        if ($excludeId) {
            $rules = $this->addExcludeIdToUniqueRules($rules, $excludeId);
        }
        $validator = new Validator($data, $rules);
        if ($validator->fails()) {
            throw new \Exception('Validation failed: ' . json_encode($validator->errors()));
        }
    }

    /**
     * Add exclude ID to unique validation rules for updates
     */
    protected function addExcludeIdToUniqueRules(array $rules, $excludeId): array
    {
        foreach ($rules as $field => $fieldRules) {
            if (is_string($fieldRules)) {
                $fieldRules = explode('|', $fieldRules);
            }
            foreach ($fieldRules as &$rule) {
                if (is_string($rule) && strpos($rule, 'unique:') === 0) {
                    $rule .= ",{$excludeId}";
                }
            }
            $rules[$field] = $fieldRules;
        }
        return $rules;
    }

    /**
     * Get a fresh model instance
     */
    protected function getModelInstance(): Model
    {
        return new $this->modelClass();
    }

    // Hook methods for extending behavior
    
    protected function beforeCreate(array &$data): void
    {
        // Override in child classes
    }
    
    protected function afterCreate(Model $record, array $data): void
    {
        // Override in child classes
    }
    
    protected function beforeUpdate(Model $record, array &$data): void
    {
        // Override in child classes
    }
    
    protected function afterUpdate(Model $record, array $data): void
    {
        // Override in child classes
    }
    
    protected function beforeDelete(Model $record): void
    {
        // Override in child classes
    }
    
    protected function afterDelete(Model $record): void
    {
        // Override in child classes
    }
}