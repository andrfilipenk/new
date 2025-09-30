# Model Relations and Custom Filtering Guide

This guide explains how to use model relations and implement custom filtering in your PHP framework.

## Table of Contents
1. [Defining Model Relations](#defining-model-relations)
2. [Basic Eager Loading](#basic-eager-loading)
3. [Constrained Eager Loading](#constrained-eager-loading)
4. [Custom Filtering Before Relations](#custom-filtering-before-relations)
5. [Advanced Filtering Techniques](#advanced-filtering-techniques)
6. [Performance Best Practices](#performance-best-practices)

## Defining Model Relations

### 1. HasOne Relationship
Use when a model has exactly one related model.

```php
// In User model
public function profile()
{
    return $this->hasOne(Profile::class, 'user_id', 'id');
    //                  related_model,  foreign_key, local_key
}
```

### 2. HasMany Relationship
Use when a model has multiple related models.

```php
// In User model
public function tasks()
{
    return $this->hasMany(Task::class, 'user_id', 'id');
    //                   related_model, foreign_key, local_key
}
```

### 3. BelongsTo Relationship
Use when a model belongs to another model.

```php
// In Task model
public function user()
{
    return $this->belongsTo(User::class, 'user_id', 'id');
    //                     related_model, foreign_key, owner_key
}
```

### 4. BelongsToMany Relationship
Use for many-to-many relationships with pivot tables.

```php
// In User model
public function roles()
{
    return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    //                         related_model, pivot_table, foreign_pivot_key, related_pivot_key
}
```

## Basic Eager Loading

### Load Single Relation
```php
$users = User::with(['profile'])->get();
```

### Load Multiple Relations
```php
$users = User::with(['profile', 'tasks', 'roles'])->get();
```

### Load Nested Relations
```php
// Load users with their tasks and task comments
$users = User::with(['tasks.comments'])->get();

// Deep nesting
$users = User::with(['tasks.comments.author'])->get();
```

## Constrained Eager Loading

### Basic Constraints
```php
$users = User::with([
    'tasks' => function($query) {
        $query->where('status', 'active')
              ->orderBy('created_at', 'desc')
              ->limit(5);
    }
])->get();
```

### Multiple Constraints
```php
$users = User::with([
    'tasks' => function($query) {
        $query->where('status', 'active')
              ->where('priority', 'high')
              ->whereRaw('end_date >= CURDATE()');
    },
    'profile' => function($query) {
        $query->where('visible', 1);
    }
])->get();
```

## Custom Filtering Before Relations

### 1. Basic Main Query Filtering
Filter the main model before loading relations:

```php
$users = User::with(['tasks', 'profile'])
    ->where('active', 1)
    ->where('created_at', '>=', date('Y-m-d', strtotime('-1 year')))
    ->orderBy('name')
    ->get();
```

### 2. Using withFilters Method
For complex main query filtering:

```php
$tasks = Task::with(['creator', 'assigned', 'comments'])
    ->withFilters(function($query) {
        $query->where('status_id', '!=', 3) // Not completed
              ->where('priority_id', '>=', 2) // Medium priority or higher
              ->whereRaw('end_date >= CURDATE()') // Not overdue
              ->orderBy('priority_id', 'desc');
    })
    ->get();
```

### 3. AndWhere for Additional Conditions
Chain multiple where conditions:

```php
$tasks = Task::with(['creator', 'assigned'])
    ->where('status_id', 1)
    ->andWhere('priority_id', '>=', 2)
    ->andWhere('begin_date', '<=', date('Y-m-d'))
    ->get();
```

## Advanced Filtering Techniques

### 1. whereWithRelation Method
Filter based on related data:

```php
$users = User::with([
    'tasks' => function($query) {
        $query->where('priority_id', 3); // High priority only in results
    }
])
->whereWithRelation('tasks', function($query) {
    $query->where('status_id', 1)  // Must have active tasks to be included
          ->where('priority_id', '>=', 2); // Medium+ priority
})
->get();
```

### 2. Complex Multi-Relation Filtering
```php
$tasks = Task::with(['creator', 'assigned', 'status', 'priority'])
    ->where('begin_date', '<=', date('Y-m-d'))
    ->andWhere('end_date', '>=', date('Y-m-d'))
    ->whereWithRelation('creator', function($query) {
        $query->where('active', 1)
              ->where('role', 'manager');
    })
    ->whereWithRelation('assigned', function($query) {
        $query->where('active', 1);
    })
    ->get();
```

### 3. Conditional Relation Loading
```php
$includeComments = $userRole === 'admin';
$relations = ['creator', 'assigned', 'status'];

if ($includeComments) {
    $relations['comments'] = function($query) {
        $query->orderBy('created_at', 'desc')->limit(5);
    };
}

$tasks = Task::with($relations)
    ->where('status_id', '!=', 4)
    ->get();
```

## Performance Best Practices

### 1. Select Only Needed Columns
```php
$users = User::with([
    'tasks' => function($query) {
        $query->select(['id', 'title', 'status_id', 'user_id'])
              ->where('status_id', 1);
    }
])
->select(['id', 'name', 'email'])
->get();
```

### 2. Use Limits on Relations
```php
$users = User::with([
    'tasks' => function($query) {
        $query->orderBy('created_at', 'desc')->limit(10);
    },
    'comments' => function($query) {
        $query->orderBy('created_at', 'desc')->limit(5);
    }
])
->get();
```

### 3. Pagination with Relations
```php
// For paginated main results
$tasks = Task::with(['creator:id,name', 'status:id,name'])
    ->select(['id', 'title', 'created_by', 'status_id'])
    ->where('status_id', 1)
    ->limit(20)
    ->offset($page * 20)
    ->get();
```

## Complete Example: Task Management System

```php
class TaskController
{
    public function getDashboardTasks($userId, $userRole)
    {
        // Build relations based on user role
        $relations = ['status', 'priority'];
        
        if ($userRole === 'admin') {
            $relations['creator'] = function($query) {
                $query->select(['id', 'name', 'email']);
            };
            $relations['assigned'] = function($query) {
                $query->select(['id', 'name', 'email']);
            };
        }
        
        // Add comments for detailed view
        if ($userRole === 'admin' || $userRole === 'manager') {
            $relations['comments'] = function($query) {
                $query->with(['author:id,name'])
                      ->orderBy('created_at', 'desc')
                      ->limit(3);
            };
        }
        
        // Build query with filters
        $query = Task::with($relations);
        
        // Apply role-based filtering
        if ($userRole !== 'admin') {
            $query->where(function($q) use ($userId) {
                $q->where('created_by', $userId)
                  ->orWhere('assigned_to', $userId);
            });
        }
        
        // Apply common filters
        $tasks = $query
            ->where('status_id', '!=', 4) // Not cancelled
            ->andWhere('status_id', '!=', 3) // Not completed
            ->withFilters(function($q) {
                $q->whereRaw('end_date >= CURDATE()') // Not overdue
                  ->orderBy('priority_id', 'desc')
                  ->orderBy('end_date', 'asc');
            })
            ->limit(50)
            ->get();
            
        return $tasks;
    }
}
```

## Available QueryBuilder Methods

### Basic Query Methods
- `where($column, $operator, $value)`
- `andWhere($column, $operator, $value)` - Alias for where()
- `orWhere($column, $operator, $value)`
- `whereIn($column, $values)`
- `whereNotIn($column, $values)`
- `whereNull($column)`
- `whereNotNull($column)`
- `whereRaw($sql, $bindings)`

### Additional Methods
- `orderBy($column, $direction)`
- `groupBy($column)`
- `having($column, $operator, $value)`
- `limit($limit)`
- `offset($offset)`
- `select($columns)`
- `selectRaw($expression)`

### Relation-Specific Methods
- `with($relations)` - Eager load relations
- `withFilters($callback)` - Apply filters to main query
- `whereWithRelation($relation, $callback)` - Filter by relation data

### Execution Methods
- `get()` - Get all results
- `first()` - Get first result
- `count()` - Count results

This comprehensive system allows you to build complex queries with optimal performance while maintaining clean, readable code.