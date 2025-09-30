# Working with BelongsToMany Relationships - Complete Guide

This is your complete guide to using `belongsToMany` relationships in your ORM system.

## ✅ **What's Working:**

Your `belongsToMany` implementation now supports:

1. **Basic Relationship Definition**
2. **Eager Loading with Relations** 
3. **Pivot Table Data Access**
4. **Attaching and Detaching Models**
5. **Synchronizing Relationships**
6. **Querying with Constraints**
7. **Custom Pivot Column Support**

## 🚀 **Quick Start**

### 1. Define the Relationship

```php
// User Model
class User extends Model 
{
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id');
    }
}

// Role Model  
class Role extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id');
    }
}
```

### 2. Basic Usage

```php
// Load user with roles
$users = User::with(['roles'])->get();
foreach ($users as $user) {
    echo "User: {$user->name}\n";
    foreach ($user->roles as $role) {
        echo "  - Role: {$role->name}\n";
    }
}

// Check if relationship exists
$user = User::first();
if ($user->roles()->exists()) {
    $roleCount = count($user->roles);
    echo "User has {$roleCount} roles";
}
```

### 3. Attaching and Detaching

```php
$user = User::find(1);
$role = Role::find(2);

// Attach a role
$user->roles()->attach($role->id);

// Attach with pivot data
$user->roles()->attach($role->id, [
    'granted' => 1,
    'expires_at' => '2024-12-31'
]);

// Detach a role
$user->roles()->detach($role->id);

// Detach all roles
$user->roles()->detach();
```

### 4. Synchronizing

```php
$user = User::find(1);

// Sync to specific role IDs (attach missing, detach extras)
$user->roles()->sync([1, 2, 3]);

// Toggle relationship
$user->roles()->toggle($role->id);
```

### 5. Working with Pivot Data

```php
// Define relationship with pivot columns
public function roles()
{
    return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
                ->withPivot('granted', 'expires_at');
}

// Access pivot data
$users = User::with(['roles'])->get();
foreach ($users as $user) {
    foreach ($user->roles as $role) {
        if (isset($role->pivot)) {
            echo "Granted: " . $role->pivot->granted;
            echo "Expires: " . $role->pivot->expires_at;
        }
    }
}
```

### 6. Querying Relationships

```php
// Filter by related data
$adminUsers = User::whereHas('roles', function($query) {
    $query->where('name', '=', 'admin');
})->get();

// Load with constraints
$users = User::with(['roles' => function($query) {
    $query->where('active', '=', 1);
}])->get();

// Direct relationship queries
$activeRoles = $user->roles()->where('active', '=', 1)->getResults();
```

## 📊 **Database Structure**

### Required Tables

For a User-Role many-to-many relationship:

```sql
-- Main tables
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    email VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    display_name VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Pivot table
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

### With Additional Pivot Columns

```sql
CREATE TABLE user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    role_id INT,
    granted BOOLEAN DEFAULT 1,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (role_id) REFERENCES roles(id)
);
```

## 🔧 **Available Methods**

### Relationship Definition
- `belongsToMany(Model, table, foreignKey, relatedKey)`
- `withPivot(...columns)` - Include additional pivot columns

### Querying
- `where(column, operator, value)` - Add where constraints
- `whereIn(column, values)` - Add whereIn constraints  
- `wherePivot(column, operator, value)` - Filter by pivot columns
- `exists()` - Check if relationship exists
- `count()` - Count related models
- `getResults()` - Get relationship results

### Manipulation
- `attach(id, attributes = [])` - Attach models
- `detach(id = null)` - Detach models  
- `sync(ids)` - Synchronize relationships
- `toggle(ids)` - Toggle relationships

## 🎯 **Real-World Examples**

### 1. User Access Control System

```php
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
        return $this->roles()->where('name', '=', $roleName)->exists();
    }
    
    public function assignRole(string $roleName): bool
    {
        $role = Role::where('name', '=', $roleName)->first();
        if ($role) {
            $role = is_array($role) ? (object)$role : $role;
            $this->roles()->attach($role->id);
            return true;
        }
        return false;
    }
}

// Usage
$user = User::find(1);
if ($user->hasRole('admin')) {
    echo "User is admin";
}

$user->assignRole('manager');
```

### 2. E-commerce Product Categories

```php
class Product extends Model
{
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_categories', 'product_id', 'category_id')
                    ->withPivot('is_primary', 'sort_order');
    }
}

class Category extends Model  
{
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_categories', 'category_id', 'product_id')
                    ->withPivot('is_primary', 'sort_order');
    }
}

// Usage
$product = Product::find(1);
$product->categories()->attach(1, ['is_primary' => 1, 'sort_order' => 1]);
$product->categories()->attach([2, 3], ['is_primary' => 0]);
```

### 3. Content Tagging System

```php
class Article extends Model
{
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'article_tags', 'article_id', 'tag_id');
    }
}

class Tag extends Model
{
    public function articles()
    {
        return $this->belongsToMany(Article::class, 'article_tags', 'tag_id', 'article_id');
    }
}

// Usage - Sync tags for an article
$article = Article::find(1);
$tagIds = [1, 2, 3, 4];
$article->tags()->sync($tagIds); // Will attach missing, detach extras
```

## ⚡ **Performance Tips**

### 1. Use Eager Loading
```php
// Bad - N+1 queries
$users = User::all();
foreach ($users as $user) {
    foreach ($user->roles as $role) { // Query for each user
        echo $role->name;
    }
}

// Good - Single query
$users = User::with(['roles'])->get();
foreach ($users as $user) {
    foreach ($user->roles as $role) { // No additional queries
        echo $role->name;
    }
}
```

### 2. Select Only Needed Columns
```php
$users = User::with(['roles' => function($query) {
    $query->select(['id', 'name']); // Only needed columns
}])->get();
```

### 3. Use Counts Instead of Loading
```php
// Instead of loading all relationships to count
$users = User::withCount(['roles'])->get();
foreach ($users as $user) {
    echo "User has {$user->roles_count} roles";
}
```

## 🛠️ **Troubleshooting**

### Common Issues

1. **Duplicate Join Error**: The system prevents duplicate joins automatically
2. **Array vs Object Results**: Convert arrays to objects when needed:
   ```php
   $role = is_array($result) ? (object)$result : $result;
   ```
3. **Missing Operators**: Always include operators in where clauses:
   ```php
   $query->where('name', '=', 'admin'); // Good
   $query->where('name', 'admin');     // May cause issues
   ```

### Debugging Tips

```php
// Check if relationship method exists
if (method_exists($user, 'roles')) {
    $roles = $user->roles;
}

// Verify pivot data
foreach ($user->roles as $role) {
    if (isset($role->pivot)) {
        var_dump($role->pivot);
    }
}
```

## 📁 **Files to Reference**

- **Core Implementation**: `app/_Core/Database/Model/BelongsToMany.php`
- **Working Examples**: `examples/test-belongs-to-many.php`
- **Real ACL System**: `app/_Core/Acl/User.php`, `Role.php`, `Permission.php`
- **Migration Example**: `migrations/2025_09_22_100000_create_acl_tables.php`

## ✨ **Summary**

Your `belongsToMany` implementation is now fully functional with:

- ✅ Basic relationship definition and usage
- ✅ Eager loading with constraints  
- ✅ Pivot table data access with `withPivot()`
- ✅ Attach/detach/sync/toggle operations
- ✅ Query constraints and filtering
- ✅ Performance optimizations
- ✅ Real-world examples (ACL system)

The system follows Laravel-like conventions while being adapted to your specific ORM architecture. You can now build complex many-to-many relationships with full pivot table support!