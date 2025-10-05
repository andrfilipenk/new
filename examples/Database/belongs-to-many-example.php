<?php
// examples/belongs-to-many-example.php
// Practical examples of belongsToMany relationships

require_once __DIR__ . '/../app/bootstrap.php';

use Core\Acl\User;
use Core\Acl\Role;
use Core\Acl\Permission;

class BelongsToManyExample
{
    // Helper method to extract values from array of objects
    private function pluck($collection, $key)
    {
        $result = [];
        foreach ($collection as $item) {
            if (is_object($item) && isset($item->$key)) {
                $result[] = $item->$key;
            } elseif (is_array($item) && isset($item[$key])) {
                $result[] = $item[$key];
            }
        }
        return $result;
    }
    
    private function implode($glue, $array)
    {
        return implode($glue, $array);
    }
    public function basicUsage()
    {
        echo "=== Basic BelongsToMany Usage ===\n";
        
        // 1. Retrieve related models
        $users = User::with(['roles'])->limit(1)->get();
        if (count($users) > 0) {
            $user = $users[0];
            echo "User: {$user->name}\n";
            echo "Roles: ";
            foreach ($user->roles as $role) {
                echo $role->name . " ";
            }
            echo "\n";
        }
        
        // 2. Check relationship existence
        if ($user && $user->roles()->exists()) {
            $roleCount = $user->roles()->count();
            echo "User has {$roleCount} roles\n";
        }
    }
    
    public function workingWithPivotData()
    {
        echo "\n=== Working with Pivot Data ===\n";
        
        // Load users with permissions including pivot data
        $users = User::with(['permissions'])->limit(3)->get();
        
        foreach ($users as $user) {
            echo "User: {$user->name}\n";
            foreach ($user->permissions as $permission) {
                echo "  Permission: {$permission->name}";
                if (isset($permission->pivot)) {
                    $granted = $permission->pivot->granted ?? 'N/A';
                    echo " (Granted: {$granted})";
                }
                echo "\n";
            }
        }
    }
    
    public function attachingAndDetaching()
    {
        echo "\n=== Attaching and Detaching Examples ===\n";
        
        $user = User::find(1);
        if (!$user) {
            echo "User not found\n";
            return;
        }
        
        echo "Original roles for {$user->name}:\n";
        $originalRoles = $user->roles;
        foreach ($originalRoles as $role) {
            echo "  - {$role->name}\n";
        }
        
        // Find a role to work with
        $testRoleResult = Role::where('name', '=', 'worker')->first();
        if (!$testRoleResult) {
            echo "Test role 'worker' not found\n";
            return;
        }
        
        // Convert array to object if needed
        $testRole = is_array($testRoleResult) ? (object)$testRoleResult : $testRoleResult;
        
        // Check if user has the role
        $hasRole = $user->roles()->where('name', '=', $testRole->name)->exists();
        echo "\nUser has 'worker' role: " . ($hasRole ? 'Yes' : 'No') . "\n";
        
        if (!$hasRole) {
            // Attach the role
            echo "Attaching 'worker' role...\n";
            $user->roles()->attach($testRole->id);
        } else {
            // Detach the role
            echo "Detaching 'worker' role...\n";
            $user->roles()->detach($testRole->id);
        }
        
        // Refresh and show updated roles
        $user = User::with(relations: ['roles'])->where('id', $user->id)->first();
        echo "Updated roles:\n";
        foreach ($user->roles as $role) {
            echo "  - {$role->name}\n";
        }
    }
    
    public function toggleRelationship()
    {
        echo "\n=== Toggle Relationship Example ===\n";
        
        $user = User::find(2);
        if (!$user) {
            echo "User not found\n";
            return;
        }
        
        $role = Role::where('name', 'leader')->first();
        if (!$role) {
            echo "Role 'leader' not found\n";
            return;
        }
        
        echo "Before toggle - User {$user->name} roles:\n";
        $beforeRoles = $user->roles;
        foreach ($beforeRoles as $r) {
            echo "  - {$r->name}\n";
        }
        
        // Toggle the relationship
        $user->roles()->toggle($role->id);
        
        // Refresh and show result
        $user = User::with(['roles'])->where('id', $user->id)->first();
        echo "\nAfter toggle - User {$user->name} roles:\n";
        foreach ($user->roles as $r) {
            echo "  - {$r->name}\n";
        }
    }
    
    public function synchronizeRelationships()
    {
        echo "\n=== Synchronize Relationships Example ===\n";
        
        $user = User::find(3);
        if (!$user) {
            echo "User not found\n";
            return;
        }
        
        echo "Before sync - User {$user->name} roles:\n";
        $beforeRoles = $user->roles;
        foreach ($beforeRoles as $role) {
            echo "  - {$role->name}\n";
        }
        
        // Get some role IDs to sync to
        $roles = Role::limit(2)->get();
        $roleIds = $this->pluck($roles, 'id');
        
        echo "\nSyncing to role IDs: " . $this->implode(', ', $roleIds) . "\n";
        
        // Sync roles (this will attach missing ones and detach extras)
        $user->roles()->sync($roleIds);
        
        // Refresh and show result
        $user = User::with(['roles'])->where('id', $user->id)->first();
        echo "After sync - User {$user->name} roles:\n";
        foreach ($user->roles as $role) {
            echo "  - {$role->name}\n";
        }
    }
    
    public function queryingRelationships()
    {
        echo "\n=== Querying Relationships Examples ===\n";
        
        // 1. Find users with specific role
        echo "Users with 'admin' role:\n";
        $adminUsers = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->get();
        
        foreach ($adminUsers as $user) {
            echo "  - {$user->name}\n";
        }
        
        // 2. Find users without any roles
        echo "\nUsers without roles:\n";
        $usersWithoutRoles = User::whereDoesntHave('roles')->get();
        foreach ($usersWithoutRoles as $user) {
            echo "  - {$user->name}\n";
        }
        
        // 3. Count relationships
        echo "\nUsers with role counts:\n";
        $usersWithCounts = User::withCount(['roles'])->limit(5)->get();
        foreach ($usersWithCounts as $user) {
            $count = $user->roles_count ?? 0;
            echo "  - {$user->name}: {$count} roles\n";
        }
    }
    
    public function helperMethods()
    {
        echo "\n=== Helper Methods Examples ===\n";
        
        $user = User::find(4);
        if (!$user) {
            echo "User not found\n";
            return;
        }
        
        echo "Testing helper methods for user: {$user->name}\n";
        
        // Test hasRole method
        $roles = ['admin', 'worker', 'leader'];
        foreach ($roles as $roleName) {
            $hasRole = method_exists($user, 'hasRole') ? $user->hasRole($roleName) : false;
            echo "  Has '{$roleName}' role: " . ($hasRole ? 'Yes' : 'No') . "\n";
        }
        
        // Test role assignment
        if (method_exists($user, 'assignRole')) {
            echo "\nTrying to assign 'worker' role:\n";
            $result = $user->assignRole('worker');
            echo "  Result: " . ($result ? 'Success' : 'Failed/Already has role') . "\n";
        }
        
        // Show current roles
        $user = User::with(['roles'])->where('id', $user->id)->first();
        echo "Current roles:\n";
        foreach ($user->roles as $role) {
            echo "  - {$role->name}\n";
        }
    }
    
    public function workingWithPermissions()
    {
        echo "\n=== Working with Permissions ===\n";
        
        // Show role permissions
        $role = Role::with(['permissions'])->first();
        if ($role) {
            echo "Role: {$role->name}\n";
            echo "Permissions:\n";
            foreach ($role->permissions as $permission) {
                echo "  - {$permission->name}: {$permission->display_name}\n";
            }
        }
        
        // Show user permissions (both direct and through roles)
        $user = User::with(['permissions', 'roles.permissions'])->find(4);
        if ($user) {
            echo "\nUser: {$user->name}\n";
            
            // Direct permissions
            echo "Direct permissions:\n";
            foreach ($user->permissions as $permission) {
                $granted = $permission->pivot->granted ?? 1;
                echo "  - {$permission->name} (Granted: {$granted})\n";
            }
            
            // Role-based permissions
            echo "Role-based permissions:\n";
            foreach ($user->roles as $role) {
                echo "  From role '{$role->name}':\n";
                foreach ($role->permissions as $permission) {
                    echo "    - {$permission->name}\n";
                }
            }
        }
    }
    
    public function performanceOptimization()
    {
        echo "\n=== Performance Optimization Examples ===\n";
        
        // 1. Eager loading multiple relationships
        echo "Loading users with roles and permissions efficiently:\n";
        $users = User::with(['roles', 'permissions'])->limit(3)->get();
        
        foreach ($users as $user) {
            echo "User: {$user->name}\n";
            $roleNames = $this->pluck($user->roles, 'name');
            echo "  Roles: " . $this->implode(', ', $roleNames) . "\n";
            echo "  Direct permissions: " . count($user->permissions) . "\n";
        }
        
        // 2. Counting relationships without loading them
        echo "\nCounting relationships efficiently:\n";
        $usersWithCounts = User::withCount(['roles', 'permissions'])->limit(3)->get();
        
        foreach ($usersWithCounts as $user) {
            $roleCount = $user->roles_count ?? 0;
            $permissionCount = $user->permissions_count ?? 0;
            echo "User: {$user->name} - Roles: {$roleCount}, Permissions: {$permissionCount}\n";
        }
    }
}

// Run examples
$examples = new BelongsToManyExample();

try {
    $examples->basicUsage();
    $examples->workingWithPivotData();
    $examples->attachingAndDetaching();
    $examples->toggleRelationship();
    $examples->synchronizeRelationships();
    $examples->queryingRelationships();
    $examples->helperMethods();
    $examples->workingWithPermissions();
    $examples->performanceOptimization();
    
    echo "\n=== All belongsToMany examples completed successfully! ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}