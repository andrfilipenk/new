<?php
// app/Core/Crud/CrudService.php
namespace Core\Crud;

use Core\Database\Model;
use Core\Validation\Validator;
use Core\Validation\ValidationException;
use Core\Http\Request;

abstract class CrudService
{
    protected string $modelClass;
    protected array $validationRules = [];
    protected array $searchableFields = [];
    protected array $relationLoads = [];

    public function __construct()
    {
        if (empty($this->modelClass)) {
            throw new \InvalidArgumentException('Model class must be defined');
        }
    }

    // CREATE
    public function create(array $data): Model
    {
        $this->validate($data);
        
        /** @var Model $model */
        $model = new $this->modelClass();
        $model->fill($data);
        
        if ($model->save()) {
            $this->afterCreate($model, $data);
            return $model;
        }
        
        throw new \RuntimeException('Failed to create record');
    }

    // READ - Single
    public function find($id): ?Model
    {
        $query = $this->modelClass::query();
        
        foreach ($this->relationLoads as $relation) {
            $query->with($relation);
        }
        
        return $query->find($id);
    }

    // READ - List with pagination and search
    public function list(array $filters = [], int $perPage = 15): array
    {
        $query = $this->modelClass::query();
        
        // Eager load relations
        foreach ($this->relationLoads as $relation) {
            $query->with($relation);
        }
        
        // Apply filters
        $this->applyFilters($query, $filters);
        
        // Apply search
        if (!empty($filters['search']) && !empty($this->searchableFields)) {
            $this->applySearch($query, $filters['search']);
        }
        
        // Apply sorting
        $sortField = $filters['sort'] ?? 'id';
        $sortDirection = $filters['direction'] ?? 'desc';
        $query->orderBy($sortField, $sortDirection);
        
        return $query->paginate($perPage);
    }

    // UPDATE
    public function update($id, array $data): Model
    {
        $model = $this->find($id);
        
        if (!$model) {
            throw new \RuntimeException('Record not found');
        }
        
        $this->validate($data, $model->getKey());
        
        $model->fill($data);
        
        if ($model->save()) {
            $this->afterUpdate($model, $data);
            return $model;
        }
        
        throw new \RuntimeException('Failed to update record');
    }

    // DELETE
    public function delete($id): bool
    {
        $model = $this->find($id);
        
        if (!$model) {
            throw new \RuntimeException('Record not found');
        }
        
        $this->beforeDelete($model);
        
        if ($model->delete()) {
            $this->afterDelete($model);
            return true;
        }
        
        return false;
    }

    // VALIDATION
    protected function validate(array $data, $exceptId = null): void
    {
        if (empty($this->validationRules)) {
            return;
        }
        
        $rules = $this->validationRules;
        
        // Add unique rule exceptions for update
        if ($exceptId !== null) {
            foreach ($rules as $field => $rule) {
                if (str_contains($rule, 'unique')) {
                    $rules[$field] = str_replace('unique:', "unique:{$this->getTableName()},$field,$exceptId", $rule);
                }
            }
        }
        
        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            throw new ValidationException('Validation failed', $validator->errors());
        }
    }

    // FILTERING
    protected function applyFilters($query, array $filters): void
    {
        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                if (str_contains($field, '.')) {
                    // Relation filter
                    [$relation, $relationField] = explode('.', $field, 2);
                    $query->whereHas($relation, function($q) use ($relationField, $value) {
                        $q->where($relationField, $value);
                    });
                } else {
                    // Direct field filter
                    $query->where($field, $value);
                }
            }
        }
    }

    // SEARCH
    protected function applySearch($query, string $searchTerm): void
    {
        $query->where(function($q) use ($searchTerm) {
            foreach ($this->searchableFields as $field) {
                $q->orWhere($field, 'LIKE', "%{$searchTerm}%");
            }
        });
    }

    // HOOKS for customization
    protected function afterCreate(Model $model, array $data): void {}
    protected function afterUpdate(Model $model, array $data): void {}
    protected function beforeDelete(Model $model): void {}
    protected function afterDelete(Model $model): void {}

    protected function getTableName(): string
    {
        return (new $this->modelClass())->getTable();
    }
}