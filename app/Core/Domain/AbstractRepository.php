<?php
// app/Core/Domain/AbstractRepository.php
namespace Core\Domain;

use Core\Database\Model;

/**
 * Abstract Repository - Base implementation for repositories
 * 
 * Provides common repository functionality for Model-based entities
 */
abstract class AbstractRepository implements RepositoryInterface
{
    protected string $modelClass;
    protected Model $model;

    public function __construct(string $modelClass = null)
    {
        $this->modelClass = $modelClass ?? $this->getModelClass();
        $this->model = new $this->modelClass();
    }

    public function findById($id): ?object
    {
        return $this->model->find($id);
    }

    public function findBy(array $criteria): array
    {
        $query = $this->model->newQuery();
        
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->get();
    }

    public function findOneBy(array $criteria): ?object
    {
        $results = $this->findBy($criteria);
        return $results[0] ?? null;
    }

    public function findAll(): array
    {
        return $this->model->all();
    }

    public function save(object $entity): object
    {
        if ($entity instanceof Model) {
            $entity->save();
            return $entity;
        }
        
        throw new \InvalidArgumentException('Entity must be a Model instance');
    }

    public function delete(object $entity): bool
    {
        if ($entity instanceof Model) {
            return $entity->delete();
        }
        
        throw new \InvalidArgumentException('Entity must be a Model instance');
    }

    public function count(array $criteria = []): int
    {
        $query = $this->model->newQuery();
        
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->count();
    }

    public function exists($id): bool
    {
        return $this->findById($id) !== null;
    }

    /**
     * Create new entity instance
     */
    public function create(array $data): object
    {
        $entity = new $this->modelClass($data);
        return $this->save($entity);
    }

    /**
     * Update entity by ID
     */
    public function update($id, array $data): ?object
    {
        $entity = $this->findById($id);
        if (!$entity) {
            return null;
        }
        
        foreach ($data as $key => $value) {
            if (property_exists($entity, $key)) {
                $entity->$key = $value;
            }
        }
        
        return $this->save($entity);
    }

    /**
     * Delete entity by ID
     */
    public function deleteById($id): bool
    {
        $entity = $this->findById($id);
        if (!$entity) {
            return false;
        }
        
        return $this->delete($entity);
    }

    /**
     * Get pagination
     */
    public function paginate(int $page = 1, int $perPage = 15, array $criteria = []): array
    {
        $query = $this->model->newQuery();
        
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        $offset = ($page - 1) * $perPage;
        $total = $query->count();
        $items = $query->limit($perPage)->offset($offset)->get();
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Get model class name - must be implemented by child classes
     */
    abstract protected function getModelClass(): string;
}