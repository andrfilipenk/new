# BelongsToMany Relationships Guide

This comprehensive guide explains how to work with many-to-many relationships in your ORM system using the `belongsToMany` method.

## Table of Contents

1. [Introduction](#introduction)
2. [Setting Up belongsToMany Relationships](#setting-up-belongstomany-relationships)
3. [Database Structure](#database-structure)
4. [Defining Relationships](#defining-relationships)
5. [Basic Usage](#basic-usage)
6. [Working with Pivot Tables](#working-with-pivot-tables)
7. [Attaching and Detaching](#attaching-and-detaching)
8. [Synchronizing Relationships](#synchronizing-relationships)
9. [Querying Relationships](#querying-relationships)
10. [Advanced Examples](#advanced-examples)
11. [Best Practices](#best-practices)

## Introduction

The `belongsToMany` relationship is used to define many-to-many relationships between models. This type of relationship requires an intermediate table (pivot table) to connect the two models.

Common examples:
- **Users ↔ Roles**: A user can have multiple roles, and a role can belong to multiple users
- **Products ↔ Categories**: A product can belong to multiple categories, and a category can have multiple products
- **Posts ↔ Tags**: A post can have multiple tags, and a tag can belong to multiple posts

## Setting Up belongsToMany Relationships

### Database Structure

For a many-to-many relationship, you need three tables:

1. **First model table** (e.g., `users`)
2. **Second model table** (e.g., `roles`) 
3. **Pivot table** (e.g., `user_roles`)

#### Example: User-Role Relationship Tables

**users table:**
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    email VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**roles table:**
```sql
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    display_name VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**user_roles pivot table:**
```sql
CREATE TABLE user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    role_id INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (role_id) REFERENCES roles(id)
);
```

### Migration Example

```php
// migrations/create_user_roles_table.php
use Core\Database\Migration;
use Core\Database\Blueprint;

class CreateUserRolesTable extends Migration
{
    public function up(): void
    {
        $this->createTable('user_roles', function(Blueprint $table) {
            $table->id();
            $table->integer('user_id')->unsigned();
            $table->integer('role_id')->unsigned();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('role_id')->references('id')->on('roles');
        });
    }

    public function down(): void
    {
        $this->dropTable('user_roles');
    }
}
```

## Defining Relationships

### Basic Relationship Definition

In your model classes, define the relationship using the `belongsToMany` method:

```php
// app/Models/User.php
class User extends Model
{
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
        //                        related_model, pivot_table, foreign_key, related_key
    }
}

// app/Models/Role.php
class Role extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id');
        //                        related_model, pivot_table, foreign_key, related_key
    }
}
```

### Method Parameters

```php
belongsToMany(
    string $related,           // Related model class
    ?string $table = null,     // Pivot table name (auto-generated if null)
    ?string $foreignPivotKey = null,  // Current model's foreign key in pivot
    ?string $relatedPivotKey = null   // Related model's foreign key in pivot
)
```

### Automatic Parameter Resolution

If parameters are omitted, the ORM will use conventions:

```php
// These are equivalent:
$this->belongsToMany(Role::class);
$this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
```

Convention rules:
- **Pivot table**: Alphabetically sorted model names joined with underscore (`role_user`)
- **Foreign keys**: Singular model name + `_id` (`user_id`, `role_id`)

## Basic Usage

### Retrieving Related Models

```php
// Get a user with their roles
$user = User::find(1);
$roles = $user->roles; // Lazy loading

// Or with eager loading
$users = User::with(['roles'])->get();
foreach ($users as $user) {
    foreach ($user->roles as $role) {
        echo "User {$user->name} has role {$role->name}\n";
    }
}
```

### Checking Relationship Existence

```php
$user = User::find(1);

// Check if user has any roles
if ($user->roles()->exists()) {
    echo "User has roles";
}

// Count roles
$roleCount = $user->roles()->count();
echo "User has {$roleCount} roles";
```

## Working with Pivot Tables

### Accessing Pivot Data

```php
$user = User::with(['roles'])->find(1);
foreach ($user->roles as $role) {
    // Access pivot data
    echo "Assigned at: " . $role->pivot->created_at;
}
```

### Including Additional Pivot Columns

If your pivot table has additional columns, use `withPivot()`:

```php
// Pivot table with extra columns
CREATE TABLE user_roles (
    id INT PRIMARY KEY,
    user_id INT,
    role_id INT,
    granted BOOLEAN DEFAULT 1,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

// Model definition
public function roles()
{
    return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
                ->withPivot('granted', 'expires_at');
}

// Usage
$user = User::with(['roles'])->find(1);
foreach ($user->roles as $role) {
    if ($role->pivot->granted) {
        echo "Role {$role->name} is granted";
        if ($role->pivot->expires_at) {
            echo " and expires at {$role->pivot->expires_at}";
        }
    }
}
```

## Attaching and Detaching

### Attaching Models

```php
$user = User::find(1);
$role = Role::find(2);

// Attach a single role
$user->roles()->attach($role->id);

// Attach with additional pivot data
$user->roles()->attach($role->id, [
    'granted' => 1,
    'expires_at' => '2024-12-31 23:59:59'
]);

// Attach multiple roles
$user->roles()->attach([2, 3, 4]);

// Attach multiple with different pivot data for each
$user->roles()->attach([
    2 => ['granted' => 1],
    3 => ['granted' => 0],
    4 => ['expires_at' => '2024-06-30']
]);
```

### Detaching Models

```php
$user = User::find(1);

// Detach a specific role
$user->roles()->detach(2);

// Detach multiple roles
$user->roles()->detach([2, 3, 4]);

// Detach all roles
$user->roles()->detach();
```

### Toggling Relationships

```php
$user = User::find(1);

// Toggle role attachment (attach if not present, detach if present)
$user->roles()->toggle(2);

// Toggle multiple roles
$user->roles()->toggle([2, 3, 4]);
```

## Synchronizing Relationships

The `sync()` method allows you to maintain a specific set of relationships:

```php
$user = User::find(1);

// Sync roles - attach missing, detach extras
$user->roles()->sync([1, 2, 3]);

// This will:
// - Attach roles 1, 2, 3 if they're not already attached
// - Detach any other roles that were previously attached
// - Leave existing relationships for roles 1, 2, 3 unchanged
```

### Sync with Pivot Data

```php
$user->roles()->sync([
    1 => ['granted' => 1],
    2 => ['granted' => 0],
    3 => ['expires_at' => '2024-12-31']
]);
```

## Querying Relationships

### Basic Queries

```php
// Get users who have a specific role
$admins = User::whereHas('roles', function($query) {
    $query->where('name', 'admin');
})->get();

// Get users who don't have any roles
$usersWithoutRoles = User::whereDoesntHave('roles')->get();

// Get users with multiple role conditions
$privilegedUsers = User::whereHas('roles', function($query) {
    $query->whereIn('name', ['admin', 'manager']);
})->get();
```

### Advanced Querying with Constraints

```php
// Load users with only active roles
$users = User::with(['roles' => function($query) {
    $query->where('active', 1);
}])->get();

// Load roles with pivot constraints
$user = User::with(['roles' => function($query) {
    $query->wherePivot('granted', 1)
          ->wherePivot('expires_at', '>', now());
}])->find(1);
```

### Counting Related Models

```php
// Load users with role counts
$users = User::withCount(['roles'])->get();
foreach ($users as $user) {
    echo "{$user->name} has {$user->roles_count} roles";
}

// Count with conditions
$users = User::withCount(['roles as active_roles_count' => function($query) {
    $query->where('active', 1);
}])->get();
```

## Advanced Examples

### Real-World ACL System Example

```php
// Models
class User extends Model
{
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }
    
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id')
                    ->withPivot('granted');
    }
    
    // Helper methods
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }
    
    public function hasPermission(string $permissionName): bool
    {
        // Check direct permissions
        $directPermission = $this->permissions()
            ->where('name', $permissionName)
            ->wherePivot('granted', 1)
            ->exists();
            
        if ($directPermission) {
            return true;
        }
        
        // Check role-based permissions
        return $this->roles()->whereHas('permissions', function($query) use ($permissionName) {
            $query->where('name', $permissionName);
        })->exists();
    }
    
    public function assignRole(string $roleName): bool
    {
        $role = Role::where('name', $roleName)->first();
        if ($role && !$this->hasRole($roleName)) {
            $this->roles()->attach($role->id);
            return true;
        }
        return false;
    }
}

class Role extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id');
    }
    
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id');
    }
    
    public function givePermission(string $permissionName): bool
    {
        $permission = Permission::where('name', $permissionName)->first();
        if ($permission) {
            $this->permissions()->attach($permission->id);
            return true;
        }
        return false;
    }
    
    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }
}

class Permission extends Model
{
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id');
    }
    
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions', 'permission_id', 'user_id')
                    ->withPivot('granted');
    }
}
```

### E-commerce Product-Category Example

```php
class Product extends Model
{
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_categories', 'product_id', 'category_id')
                    ->withPivot('is_primary', 'sort_order');
    }
    
    public function primaryCategory()
    {
        return $this->belongsToMany(Category::class, 'product_categories', 'product_id', 'category_id')
                    ->wherePivot('is_primary', 1)
                    ->first();
    }
}

class Category extends Model
{
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_categories', 'category_id', 'product_id')
                    ->withPivot('is_primary', 'sort_order')
                    ->orderByPivot('sort_order');
    }
    
    public function featuredProducts()
    {
        return $this->belongsToMany(Product::class, 'product_categories', 'category_id', 'product_id')
                    ->wherePivot('is_primary', 1)
                    ->where('featured', 1);
    }
}

// Usage
$category = Category::find(1);
$products = $category->products; // All products in category

// Get products with pivot data
foreach ($category->products as $product) {
    $isPrimary = $product->pivot->is_primary;
    $sortOrder = $product->pivot->sort_order;
    echo "Product: {$product->name}, Primary: {$isPrimary}, Order: {$sortOrder}";
}
```

## Best Practices

### 1. Naming Conventions

```php
// Good: Follow conventions for automatic resolution
public function roles()
{
    return $this->belongsToMany(Role::class); // Will use 'role_user' table
}

// Good: Explicit naming when conventions don't fit
public function permissions()
{
    return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id');
}
```

### 2. Use Helper Methods

```php
class User extends Model 
{
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
    
    // Helper methods for common operations
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }
    
    public function assignRole(string $role): void
    {
        $roleModel = Role::where('name', $role)->first();
        if ($roleModel) {
            $this->roles()->attach($roleModel->id);
        }
    }
    
    public function removeRole(string $role): void
    {
        $roleModel = Role::where('name', $role)->first();
        if ($roleModel) {
            $this->roles()->detach($roleModel->id);
        }
    }
}
```

### 3. Eager Loading for Performance

```php
// Bad: N+1 queries
$users = User::all();
foreach ($users as $user) {
    foreach ($user->roles as $role) { // Query for each user
        echo $role->name;
    }
}

// Good: Eager loading
$users = User::with(['roles'])->get();
foreach ($users as $user) {
    foreach ($user->roles as $role) { // No additional queries
        echo $role->name;
    }
}
```

### 4. Use Transactions for Bulk Operations

```php
class UserService extends BaseService
{
    public function assignMultipleRoles(User $user, array $roleIds): void
    {
        $this->db->beginTransaction();
        try {
            $user->roles()->sync($roleIds);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
```

### 5. Validate Before Attach/Detach

```php
class User extends Model
{
    public function assignRole(string $roleName): bool
    {
        $role = Role::where('name', $roleName)->first();
        
        if (!$role) {
            throw new InvalidArgumentException("Role '{$roleName}' does not exist");
        }
        
        if ($this->hasRole($roleName)) {
            return false; // Already has role
        }
        
        $this->roles()->attach($role->id);
        return true;
    }
}
```

### 6. Use Pivot Timestamps

Always include timestamps in your pivot tables:

```php
// Migration
$table->timestamps(); // Creates created_at and updated_at

// Model - timestamps are automatically handled
public function roles()
{
    return $this->belongsToMany(Role::class)->withTimestamps();
}
```

## Common Patterns and Use Cases

### User Management System

```php
// Assign default role to new users
class UserService extends BaseService
{
    public function createUser(array $userData): User
    {
        $user = User::create($userData);
        
        // Assign default role
        $defaultRole = Role::where('name', 'user')->first();
        if ($defaultRole) {
            $user->roles()->attach($defaultRole->id);
        }
        
        return $user;
    }
}
```

### Content Management

```php
class Article extends Model
{
    public function tags()
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }
    
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'article_categories')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }
}

// Usage
$article = Article::create(['title' => 'My Article', 'content' => '...']);
$article->tags()->attach([1, 2, 3]); // Attach tags
$article->categories()->attach(1, ['is_primary' => 1]); // Primary category
$article->categories()->attach([2, 3]); // Additional categories
```

This guide covers all the essential aspects of working with `belongsToMany` relationships in your ORM. The relationship type is powerful for modeling complex many-to-many associations with full support for pivot table data and operations.