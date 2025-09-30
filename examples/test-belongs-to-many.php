<?php
// Test simple belongsToMany functionality
require_once __DIR__ . '/../app/bootstrap.php';

use Core\Acl\User;
use Core\Acl\Role;

echo "=== Simple BelongsToMany Test ===\n";

// Test basic relationship
$users = User::with(['roles'])->limit(1)->get();
if (count($users) > 0) {
    $user = $users[0];
    echo "User: {$user->name}\n";
    echo "Roles: ";
    foreach ($user->roles as $role) {
        echo $role->name . " ";
    }
    echo "\n";
    
    // Test role count
    $roleCount = count($user->roles);
    echo "Role count: {$roleCount}\n";
    
    // Test simple attach/detach
    echo "\nTesting attach/detach:\n";
    $role = Role::first();
    if ($role) {
        $role = is_array($role) ? (object)$role : $role;
        echo "Found role: {$role->name}\n";
        
        try {
            $user->roles()->attach($role->id);
            echo "Attach successful\n";
        } catch (Exception $e) {
            echo "Attach error: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n=== Test completed ===\n";