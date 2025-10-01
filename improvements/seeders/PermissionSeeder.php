<?php
// seeders/PermissionSeeder.php
use Core\Database\Seeder;
use Core\Acl\Permission;
use Core\Acl\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Define comprehensive permissions for different modules
        $permissions = [
            // User Management
            ['name' => 'user.list', 'display_name' => 'List Users', 'module' => 'admin', 'controller' => 'user', 'action' => 'index'],
            ['name' => 'user.view', 'display_name' => 'View User Details', 'module' => 'admin', 'controller' => 'user', 'action' => 'view'],
            ['name' => 'user.create', 'display_name' => 'Create User', 'module' => 'admin', 'controller' => 'user', 'action' => 'create'],
            ['name' => 'user.edit', 'display_name' => 'Edit User', 'module' => 'admin', 'controller' => 'user', 'action' => 'edit'],
            ['name' => 'user.delete', 'display_name' => 'Delete User', 'module' => 'admin', 'controller' => 'user', 'action' => 'delete'],
            
            // Group Management
            ['name' => 'group.list', 'display_name' => 'List Groups', 'module' => 'admin', 'controller' => 'group', 'action' => 'index'],
            ['name' => 'group.create', 'display_name' => 'Create Group', 'module' => 'admin', 'controller' => 'group', 'action' => 'create'],
            ['name' => 'group.edit', 'display_name' => 'Edit Group', 'module' => 'admin', 'controller' => 'group', 'action' => 'edit'],
            ['name' => 'group.delete', 'display_name' => 'Delete Group', 'module' => 'admin', 'controller' => 'group', 'action' => 'delete'],
            
            // Holiday Management
            ['name' => 'holiday.list', 'display_name' => 'List Holiday Requests', 'module' => 'admin', 'controller' => 'holidays', 'action' => 'index'],
            ['name' => 'holiday.create', 'display_name' => 'Create Holiday Request', 'module' => 'admin', 'controller' => 'holidays', 'action' => 'create'],
            ['name' => 'holiday.approve', 'display_name' => 'Approve Holiday Requests', 'module' => 'admin', 'controller' => 'holidays', 'action' => 'manage'],
            ['name' => 'holiday.view-own', 'display_name' => 'View Own Holiday Requests', 'module' => 'admin', 'controller' => 'holidays', 'action' => 'myrequests'],
            
            // Access Control
            ['name' => 'acl.roles', 'display_name' => 'Manage Roles', 'module' => 'admin', 'controller' => 'access', 'action' => 'roles'],
            ['name' => 'acl.permissions', 'display_name' => 'Manage Permissions', 'module' => 'admin', 'controller' => 'access', 'action' => 'permissions'],
            ['name' => 'acl.user-roles', 'display_name' => 'Assign User Roles', 'module' => 'admin', 'controller' => 'access', 'action' => 'userRoles'],
            ['name' => 'acl.user-permissions', 'display_name' => 'Assign User Permissions', 'module' => 'admin', 'controller' => 'access', 'action' => 'userPermissions'],
            
            // Task Management (existing)
            ['name' => 'task.list', 'display_name' => 'List Tasks', 'module' => 'base', 'controller' => 'task', 'action' => 'list'],
            ['name' => 'task.view', 'display_name' => 'View Task Details', 'module' => 'base', 'controller' => 'task', 'action' => 'view'],
            ['name' => 'task.create', 'display_name' => 'Create Task', 'module' => 'base', 'controller' => 'task', 'action' => 'create'],
            ['name' => 'task.edit', 'display_name' => 'Edit Task', 'module' => 'base', 'controller' => 'task', 'action' => 'edit'],
            ['name' => 'task.delete', 'display_name' => 'Delete Task', 'module' => 'base', 'controller' => 'task', 'action' => 'delete'],
            ['name' => 'task.assign', 'display_name' => 'Assign Task', 'module' => 'base', 'controller' => 'task', 'action' => 'assign'],
            ['name' => 'task.comment', 'display_name' => 'Comment on Tasks', 'module' => 'base', 'controller' => 'task', 'action' => 'comment'],
            
            // Reports & Analytics
            ['name' => 'report.view', 'display_name' => 'View Reports', 'module' => 'admin', 'controller' => 'reports', 'action' => 'index'],
            ['name' => 'report.export', 'display_name' => 'Export Reports', 'module' => 'admin', 'controller' => 'reports', 'action' => 'export'],
            
            // System Settings
            ['name' => 'system.settings', 'display_name' => 'System Settings', 'module' => 'admin', 'controller' => 'settings', 'action' => 'index'],
            ['name' => 'system.backup', 'display_name' => 'System Backup', 'module' => 'admin', 'controller' => 'settings', 'action' => 'backup'],
        ];
        
        foreach ($permissions as $permissionData) {
            $permission = new Permission();
            $permission->name = $permissionData['name'];
            $permission->display_name = $permissionData['display_name'];
            $permission->module = $permissionData['module'];
            $permission->controller = $permissionData['controller'];
            $permission->action = $permissionData['action'];
            $permission->save();
            
            echo "Created permission: {$permissionData['name']}\n";
        }
        
        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }
    
    protected function assignPermissionsToRoles(): void
    {
        // Define role permissions
        $rolePermissions = [
            'user' => [
                'task.list', 'task.view', 'task.comment',
                'holiday.create', 'holiday.view-own'
            ],
            'worker' => [
                'task.list', 'task.view', 'task.create', 'task.edit', 
                'task.comment', 'holiday.create', 'holiday.view-own',
                'user.view'
            ],
            'leader' => [
                'task.list', 'task.view', 'task.create', 'task.edit', 
                'task.assign', 'task.comment', 'holiday.list', 
                'holiday.approve', 'holiday.create', 'holiday.view-own',
                'user.list', 'user.view', 'group.list', 'report.view'
            ],
            'admin' => [
                // All permissions
                'user.*', 'group.*', 'holiday.*', 'task.*', 
                'acl.*', 'report.*', 'system.*'
            ],
            'master' => [
                // All permissions + system level
                '*'
            ]
        ];
        
        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::find('name', $roleName)->first();
            if (!$role) continue;
            
            foreach ($permissions as $permissionPattern) {
                if ($permissionPattern === '*') {
                    // Assign all permissions
                    $allPermissions = Permission::all();
                    foreach ($allPermissions as $permission) {
                        $role->givePermission($permission->name);
                    }
                } elseif (str_ends_with($permissionPattern, '*')) {
                    // Wildcard permissions
                    $prefix = rtrim($permissionPattern, '*');
                    $matchingPermissions = Permission::where('name', 'LIKE', $prefix . '%')->get();
                    foreach ($matchingPermissions as $permission) {
                        $role->givePermission($permission->name);
                    }
                } else {
                    // Exact permission
                    $role->givePermission($permissionPattern);
                }
            }
            
            echo "Assigned permissions to role: {$roleName}\n";
        }
    }
}