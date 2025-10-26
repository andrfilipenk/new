<?php

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use Core\Request;
use Core\Response;
use App\Models\Role;
use App\Models\Permission;
use Core\Validation\Validator;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $roles = Role::all();
        foreach ($roles as $role) {
            $role->permissions = $role->permissions();
        }
        
        return $this->view('admin/roles/index', ['roles' => $roles]);
    }
    
    public function create(Request $request): Response
    {
        $permissions = Permission::all();
        return $this->view('admin/roles/create', ['permissions' => $permissions]);
    }
    
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles',
            'description' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return $this->redirect('/admin/roles/create')->with('error', 'Validation failed');
        }
        
        $role = Role::create($validator->validated());
        return $this->redirect('/admin/roles')->with('success', 'Role created successfully');
    }
    
    public function show(Request $request, int $id): Response
    {
        $role = Role::find($id);
        if (!$role) {
            return $this->redirect('/admin/roles')->with('error', 'Role not found');
        }
        
        $role->permissions = $role->permissions();
        return $this->view('admin/roles/show', ['role' => $role]);
    }
    
    public function edit(Request $request, int $id): Response
    {
        $role = Role::find($id);
        if (!$role) {
            return $this->redirect('/admin/roles')->with('error', 'Role not found');
        }
        
        $permissions = Permission::all();
        $rolePermissions = $role->permissions();
        
        return $this->view('admin/roles/edit', [
            'role' => $role,
            'permissions' => $permissions,
            'rolePermissions' => $rolePermissions,
        ]);
    }
    
    public function update(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return $this->redirect("/admin/roles/{$id}/edit")->with('error', 'Validation failed');
        }
        
        $role = Role::find($id);
        if (!$role) {
            return $this->redirect('/admin/roles')->with('error', 'Role not found');
        }
        
        $role->update($validator->validated());
        return $this->redirect('/admin/roles')->with('success', 'Role updated successfully');
    }
    
    public function destroy(Request $request, int $id): Response
    {
        $role = Role::find($id);
        if (!$role) {
            return $this->redirect('/admin/roles')->with('error', 'Role not found');
        }
        
        $role->delete();
        return $this->redirect('/admin/roles')->with('success', 'Role deleted successfully');
    }
}
