<?php
// app/_Core/Auth/AuthService.php
namespace Core\Auth;

use Admin\Model\User;
use Core\Di\Injectable;

class AuthService
{
    use Injectable;
    
    public function attempt(string $identifier, string $password): bool
    {
        // Try to find user by email or custom_id
        $user = User::where('email', $identifier)
                   ->orWhere('custom_id', $identifier)
                   ->first();
        
        if (!$user || !$user->verifyPassword($password)) {
            return false;
        }
        
        // Store user in session
        $this->login($user);
        return true;
    }
    
    public function login(User $user): void
    {
        $session = $this->getDI()->get('session');
        // Load user roles for ACL
        $roles = $user->roles()->pluck('name')->toArray();
        $primaryRole = $roles[0] ?? 'user';
        $session->set('user', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'custom_id' => $user->custom_id,
            'role' => $primaryRole,
            'roles' => $roles,
            'permissions' => $this->getUserPermissions($user)
        ]);
        // Update last login
        $user->last_login = date('Y-m-d H:i:s');
        $user->save();
    }
    
    public function logout(): void
    {
        $session = $this->getDI()->get('session');
        $session->remove('user');
        $session->destroy();
    }
    
    public function user(): ?array
    {
        $session = $this->getDI()->get('session');
        return $session->get('user');
    }
    
    public function check(): bool
    {
        return $this->user() !== null;
    }
    
    public function hasPermission(string $permission): bool
    {
        $user = $this->user();
        if (!$user) return false;
        return in_array($permission, $user['permissions'] ?? []);
    }
    
    protected function getUserPermissions(User $user): array
    {
        $permissions = [];
        // Get role-based permissions
        foreach ($user->roles as $role) {
            foreach ($role->permissions as $permission) {
                $permissions[] = $permission->name;
            }
        }
        // Get direct user permissions
        foreach ($user->permissions as $permission) {
            if ($permission->pivot_granted) {
                $permissions[] = $permission->name;
            }
        }
        return array_unique($permissions);
    }
}