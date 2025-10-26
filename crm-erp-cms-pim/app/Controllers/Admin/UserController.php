<?php

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use Core\Request;
use Core\Response;
use App\Models\User;
use App\Models\Role;
use Core\Validation\Validator;
use Core\Security\PasswordHasher;

/**
 * User Management Controller
 * 
 * Handles CRUD operations for user management
 */
class UserController extends Controller
{
    /**
     * Display list of users
     */
    public function index(Request $request): Response
    {
        try {
            $users = User::all();
            
            // Load roles for each user
            foreach ($users as $user) {
                $user->roles = $user->roles();
            }
            
            if ($request->expectsJson()) {
                return $this->json(['users' => $users]);
            }
            
            return $this->view('admin/users/index', [
                'users' => $users,
            ]);
        } catch (\Exception $e) {
            logger()->error('Failed to fetch users', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return $this->json(['error' => 'Failed to fetch users'], 500);
            }
            
            return $this->view('admin/users/index', [
                'users' => [],
                'error' => 'Failed to load users',
            ]);
        }
    }
    
    /**
     * Show create user form
     */
    public function create(Request $request): Response
    {
        $roles = Role::all();
        
        return $this->view('admin/users/create', [
            'roles' => $roles,
        ]);
    }
    
    /**
     * Store new user
     */
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => 'required|integer',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->json(['errors' => $validator->errors()], 422);
            }
            
            return $this->redirect('/admin/users/create')
                ->with('error', 'Validation failed');
        }
        
        try {
            $data = $validator->validated();
            
            // Hash password
            $data['password'] = PasswordHasher::hash($data['password']);
            
            $roleId = $data['role_id'];
            unset($data['role_id']);
            
            // Create user
            $user = User::create($data);
            
            // Assign role
            $user->assignRole($roleId);
            
            if ($request->expectsJson()) {
                return $this->json([
                    'user' => $user,
                    'message' => 'User created successfully'
                ], 201);
            }
            
            return $this->redirect('/admin/users')
                ->with('success', 'User created successfully');
        } catch (\Exception $e) {
            logger()->error('Failed to create user', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return $this->json(['error' => 'Failed to create user'], 500);
            }
            
            return $this->redirect('/admin/users/create')
                ->with('error', 'Failed to create user');
        }
    }
    
    /**
     * Show user details
     */
    public function show(Request $request, int $id): Response
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                if ($request->expectsJson()) {
                    return $this->json(['error' => 'User not found'], 404);
                }
                
                return $this->redirect('/admin/users')
                    ->with('error', 'User not found');
            }
            
            $user->roles = $user->roles();
            
            if ($request->expectsJson()) {
                return $this->json(['user' => $user]);
            }
            
            return $this->view('admin/users/show', [
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            logger()->error('Failed to fetch user', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return $this->json(['error' => 'Failed to fetch user'], 500);
            }
            
            return $this->redirect('/admin/users')
                ->with('error', 'Failed to load user');
        }
    }
    
    /**
     * Show edit user form
     */
    public function edit(Request $request, int $id): Response
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return $this->redirect('/admin/users')
                    ->with('error', 'User not found');
            }
            
            $roles = Role::all();
            $userRoles = $user->roles();
            
            return $this->view('admin/users/edit', [
                'user' => $user,
                'roles' => $roles,
                'userRoles' => $userRoles,
            ]);
        } catch (\Exception $e) {
            logger()->error('Failed to load user edit form', ['error' => $e->getMessage()]);
            
            return $this->redirect('/admin/users')
                ->with('error', 'Failed to load user');
        }
    }
    
    /**
     * Update user
     */
    public function update(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'nullable|string|min:8',
            'role_id' => 'required|integer',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return $this->json(['errors' => $validator->errors()], 422);
            }
            
            return $this->redirect("/admin/users/{$id}/edit")
                ->with('error', 'Validation failed');
        }
        
        try {
            $user = User::find($id);
            
            if (!$user) {
                if ($request->expectsJson()) {
                    return $this->json(['error' => 'User not found'], 404);
                }
                
                return $this->redirect('/admin/users')
                    ->with('error', 'User not found');
            }
            
            $data = $validator->validated();
            
            // Update password only if provided
            if (!empty($data['password'])) {
                $data['password'] = PasswordHasher::hash($data['password']);
            } else {
                unset($data['password']);
            }
            
            $roleId = $data['role_id'];
            unset($data['role_id']);
            
            // Update user
            $user->update($data);
            
            // Update role
            $user->syncRoles([$roleId]);
            
            if ($request->expectsJson()) {
                return $this->json([
                    'user' => $user,
                    'message' => 'User updated successfully'
                ]);
            }
            
            return $this->redirect('/admin/users')
                ->with('success', 'User updated successfully');
        } catch (\Exception $e) {
            logger()->error('Failed to update user', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return $this->json(['error' => 'Failed to update user'], 500);
            }
            
            return $this->redirect("/admin/users/{$id}/edit")
                ->with('error', 'Failed to update user');
        }
    }
    
    /**
     * Delete user
     */
    public function destroy(Request $request, int $id): Response
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                if ($request->expectsJson()) {
                    return $this->json(['error' => 'User not found'], 404);
                }
                
                return $this->redirect('/admin/users')
                    ->with('error', 'User not found');
            }
            
            // Prevent deleting yourself
            if ($user->id === auth()->id()) {
                if ($request->expectsJson()) {
                    return $this->json(['error' => 'Cannot delete yourself'], 400);
                }
                
                return $this->redirect('/admin/users')
                    ->with('error', 'Cannot delete yourself');
            }
            
            $user->delete();
            
            if ($request->expectsJson()) {
                return $this->json(['message' => 'User deleted successfully']);
            }
            
            return $this->redirect('/admin/users')
                ->with('success', 'User deleted successfully');
        } catch (\Exception $e) {
            logger()->error('Failed to delete user', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return $this->json(['error' => 'Failed to delete user'], 500);
            }
            
            return $this->redirect('/admin/users')
                ->with('error', 'Failed to delete user');
        }
    }
}
