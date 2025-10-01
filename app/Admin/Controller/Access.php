<?php
// app/Admin/Controller/Access.php
namespace Admin\Controller;

use Core\Mvc\Controller;
use Core\Acl\Role;
use Core\Acl\Permission;
use Core\Acl\User;
use Admin\Model\User as AdminUser;

class Access extends Controller
{
    // List all roles
    public function rolesAction()
    {
        $roles = Role::all();
        return $this->render('access/roles', ['roles' => $roles]);
    }

    // Create new role
    public function createRoleAction()
    {
        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            $name = trim($data['name'] ?? '');
            $description = trim($data['description'] ?? '');

            if ($name) {
                $role = new Role();
                $role->name = $name;
                $role->description = $description;
                
                if ($role->save()) {
                    $this->flashSuccess('Role created successfully.');
                    return $this->redirect('admin/access/roles');
                } else {
                    $this->flashError('Failed to create role.');
                }
            } else {
                $this->flashError('Role name is required.');
            }
        }

        return $this->render('access/create-role');
    }

    // Edit role
    public function editRoleAction()
    {
        $id = $this->getDispatcher()->getParam('id');
        $role = Role::find($id);
        
        if (!$role) {
            $this->flashError('Role not found.');
            return $this->redirect('admin/access/roles');
        }

        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            $name = trim($data['name'] ?? '');
            $description = trim($data['description'] ?? '');

            if ($name) {
                $role->name = $name;
                $role->description = $description;
                
                if ($role->save()) {
                    $this->flashSuccess('Role updated successfully.');
                    return $this->redirect('admin/access/roles');
                } else {
                    $this->flashError('Failed to update role.');
                }
            } else {
                $this->flashError('Role name is required.');
            }
        }

        return $this->render('access/edit-role', ['role' => $role]);
    }

    // Delete role
    public function deleteRoleAction()
    {
        $id = $this->getDispatcher()->getParam('id');
        $role = Role::find($id);
        
        if ($role) {
            if ($role->delete()) {
                $this->flashSuccess('Role deleted successfully.');
            } else {
                $this->flashError('Failed to delete role.');
            }
        } else {
            $this->flashError('Role not found.');
        }
        
        return $this->redirect('admin/access/roles');
    }

    // List all permissions
    public function permissionsAction()
    {
        $permissions = Permission::all();
        return $this->render('access/permissions', ['permissions' => $permissions]);
    }

    // Create new permission
    public function createPermissionAction()
    {
        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            $name = trim($data['name'] ?? '');
            $resource = trim($data['resource'] ?? '');
            $action = trim($data['action'] ?? '');

            if ($name && $resource && $action) {
                $permission = new Permission();
                $permission->name = $name;
                $permission->resource = $resource;
                $permission->action = $action;
                
                if ($permission->save()) {
                    $this->flashSuccess('Permission created successfully.');
                    return $this->redirect('admin/access/permissions');
                } else {
                    $this->flashError('Failed to create permission.');
                }
            } else {
                $this->flashError('All fields are required.');
            }
        }

        return $this->render('access/create-permission');
    }

    // Edit permission
    public function editPermissionAction()
    {
        $id = $this->getDispatcher()->getParam('id');
        $permission = Permission::find($id);
        
        if (!$permission) {
            $this->flashError('Permission not found.');
            return $this->redirect('admin/access/permissions');
        }

        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            $name = trim($data['name'] ?? '');
            $resource = trim($data['resource'] ?? '');
            $action = trim($data['action'] ?? '');

            if ($name && $resource && $action) {
                $permission->name = $name;
                $permission->resource = $resource;
                $permission->action = $action;
                
                if ($permission->save()) {
                    $this->flashSuccess('Permission updated successfully.');
                    return $this->redirect('admin/access/permissions');
                } else {
                    $this->flashError('Failed to update permission.');
                }
            } else {
                $this->flashError('All fields are required.');
            }
        }

        return $this->render('access/edit-permission', ['permission' => $permission]);
    }

    // Delete permission
    public function deletePermissionAction()
    {
        $id = $this->getDispatcher()->getParam('id');
        $permission = Permission::find($id);
        
        if ($permission) {
            if ($permission->delete()) {
                $this->flashSuccess('Permission deleted successfully.');
            } else {
                $this->flashError('Failed to delete permission.');
            }
        } else {
            $this->flashError('Permission not found.');
        }
        
        return $this->redirect('admin/access/permissions');
    }

    // Main access control overview
    public function indexAction()
    {
        $rolesCount = count(Role::all());
        $permissionsCount = count(Permission::all());
        
        return $this->render('access/index', [
            'rolesCount' => $rolesCount,
            'permissionsCount' => $permissionsCount
        ]);
    }

    // Manage user roles
    public function userRolesAction()
    {
        $users = AdminUser::with(['roles'])->get();
        return $this->render('access/user-roles', ['users' => $users]);
    }

    // Assign/remove roles for a specific user
    public function manageUserRolesAction()
    {
        $id = $this->getDispatcher()->getParam('id');
        $user = AdminUser::find($id);
        
        if (!$user) {
            $this->flashError('User not found.');
            return $this->redirect('admin/access/user-roles');
        }

        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            $action = $data['action'] ?? '';
            $roleId = (int)($data['role_id'] ?? 0);

            if ($roleId > 0) {
                if ($action === 'assign') {
                    $user->assignRoleById($roleId);
                    $this->flashSuccess('Role assigned to user.');
                } elseif ($action === 'remove') {
                    $user->removeRoleById($roleId);
                    $this->flashSuccess('Role removed from user.');
                }
            }
            
            return $this->redirect('admin/access/manage-user-roles/' . $id);
        }

        $allRoles = Role::all();
        $userRoles = $user->roles()->getResults();
        
        return $this->render('access/manage-user-roles', [
            'user' => $user,
            'userRoles' => $userRoles,
            'allRoles' => $allRoles
        ]);
    }

    // Manage user permissions
    public function userPermissionsAction()
    {
        $users = AdminUser::all();
        return $this->render('access/user-permissions', ['users' => $users]);
    }

    // Assign/remove permissions for a specific user
    public function manageUserPermissionsAction()
    {
        $id = $this->getDispatcher()->getParam('id');
        $user = AdminUser::find($id);
        
        if (!$user) {
            $this->flashError('User not found.');
            return $this->redirect('admin/access/user-permissions');
        }

        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            $action = $data['action'] ?? '';
            $permissionId = (int)($data['permission_id'] ?? 0);
            $granted = isset($data['granted']) ? (int)$data['granted'] : 1;

            if ($permissionId > 0) {
                if ($action === 'assign') {
                    $this->assignUserPermission($user->id, $permissionId, $granted);
                    $this->flashSuccess('Permission assigned to user.');
                } elseif ($action === 'remove') {
                    $this->removeUserPermission($user->id, $permissionId);
                    $this->flashSuccess('Permission removed from user.');
                }
            }
            
            return $this->redirect('admin/access/manage-user-permissions/' . $id);
        }

        $allPermissions = Permission::all();
        $userPermissions = $this->getUserPermissions($user->id);
        
        return $this->render('access/manage-user-permissions', [
            'user' => $user,
            'userPermissions' => $userPermissions,
            'allPermissions' => $allPermissions
        ]);
    }

    // Helper method to assign user permission
    private function assignUserPermission(int $userId, int $permissionId, int $granted = 1)
    {
        $db = AdminUser::db();
        
        // Check if permission already exists
        $existing = $db->table('acl_user_permission')
            ->where('user_id', $userId)
            ->where('permission_id', $permissionId)
            ->first();
            
        if ($existing) {
            // Update existing permission
            $db->table('acl_user_permission')
                ->where('user_id', $userId)
                ->where('permission_id', $permissionId)
                ->update(['granted' => $granted, 'updated_at' => date('Y-m-d H:i:s')]);
        } else {
            // Insert new permission
            $db->table('acl_user_permission')->insert([
                'user_id' => $userId,
                'permission_id' => $permissionId,
                'granted' => $granted,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    // Helper method to remove user permission
    private function removeUserPermission(int $userId, int $permissionId)
    {
        $db = AdminUser::db();
        $db->table('acl_user_permission')
            ->where('user_id', $userId)
            ->where('permission_id', $permissionId)
            ->delete();
    }

    // Helper method to get user permissions
    private function getUserPermissions(int $userId): array
    {
        $db = AdminUser::db();
        $results = $db->table('acl_user_permission')
            ->select(['acl_permission.*', 'acl_user_permission.granted'])
            ->join('acl_permission', 'acl_user_permission.permission_id', '=', 'acl_permission.id')
            ->where('acl_user_permission.user_id', $userId)
            ->get();
            
        return array_map([Permission::class, 'newFromBuilder'], $results);
    }
}