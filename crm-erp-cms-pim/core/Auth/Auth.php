<?php

namespace Core\Auth;

use App\Models\User;
use Core\Session\SessionManager;
use Core\Security\PasswordHasher;

/**
 * Authentication Manager
 * 
 * Handles user authentication
 */
class Auth
{
    protected static ?User $user = null;
    
    /**
     * Attempt to authenticate a user
     */
    public static function attempt(array $credentials, bool $remember = false): bool
    {
        $email = $credentials['email'] ?? null;
        $password = $credentials['password'] ?? null;
        
        if (!$email || !$password) {
            return false;
        }
        
        // Find user by email
        $users = \Core\Database\DB::table('users')
            ->where('email', '=', $email)
            ->where('is_active', '=', 1)
            ->whereNull('deleted_at')
            ->get();
        
        if (empty($users)) {
            return false;
        }
        
        $userData = $users[0];
        $user = User::hydrate($userData);
        
        // Verify password
        if (!PasswordHasher::verify($password, $user->password)) {
            return false;
        }
        
        // Login the user
        static::login($user, $remember);
        
        return true;
    }
    
    /**
     * Login a user
     */
    public static function login(User $user, bool $remember = false): void
    {
        // Store user ID in session
        SessionManager::set('user_id', $user->id);
        SessionManager::regenerate();
        
        // Update last login
        $user->update(['last_login_at' => date('Y-m-d H:i:s')]);
        
        // Handle remember me
        if ($remember) {
            $token = static::generateRememberToken();
            $user->update(['remember_token' => $token]);
            
            // Set cookie (valid for 30 days)
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
        }
        
        static::$user = $user;
    }
    
    /**
     * Logout the user
     */
    public static function logout(): void
    {
        // Clear user from static property
        static::$user = null;
        
        // Clear session
        SessionManager::forget('user_id');
        
        // Clear remember cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
        
        SessionManager::regenerate();
    }
    
    /**
     * Check if user is authenticated
     */
    public static function check(): bool
    {
        return static::user() !== null;
    }
    
    /**
     * Check if user is guest
     */
    public static function guest(): bool
    {
        return !static::check();
    }
    
    /**
     * Get authenticated user
     */
    public static function user(): ?User
    {
        if (static::$user !== null) {
            return static::$user;
        }
        
        // Try to get user from session
        $userId = SessionManager::get('user_id');
        
        if ($userId) {
            static::$user = User::find($userId);
            return static::$user;
        }
        
        // Try to get user from remember token
        if (isset($_COOKIE['remember_token'])) {
            $token = $_COOKIE['remember_token'];
            
            $users = \Core\Database\DB::table('users')
                ->where('remember_token', '=', $token)
                ->where('is_active', '=', 1)
                ->whereNull('deleted_at')
                ->get();
            
            if (!empty($users)) {
                $user = User::hydrate($users[0]);
                static::login($user, true);
                return $user;
            }
        }
        
        return null;
    }
    
    /**
     * Get authenticated user ID
     */
    public static function id(): ?int
    {
        $user = static::user();
        return $user ? $user->id : null;
    }
    
    /**
     * Login using user ID
     */
    public static function loginUsingId(int $userId, bool $remember = false): bool
    {
        $user = User::find($userId);
        
        if (!$user || !$user->is_active) {
            return false;
        }
        
        static::login($user, $remember);
        return true;
    }
    
    /**
     * Generate remember token
     */
    protected static function generateRememberToken(): string
    {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Create password reset token
     */
    public static function createPasswordResetToken(string $email): ?string
    {
        $users = \Core\Database\DB::table('users')
            ->where('email', '=', $email)
            ->get();
        
        if (empty($users)) {
            return null;
        }
        
        // Delete old tokens
        \Core\Database\DB::table('password_resets')
            ->where('email', '=', $email)
            ->delete();
        
        // Create new token
        $token = bin2hex(random_bytes(32));
        
        \Core\Database\DB::table('password_resets')->insert([
            'email' => $email,
            'token' => hash('sha256', $token),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        return $token;
    }
    
    /**
     * Reset password using token
     */
    public static function resetPassword(string $email, string $token, string $newPassword): bool
    {
        $hashedToken = hash('sha256', $token);
        
        $resets = \Core\Database\DB::table('password_resets')
            ->where('email', '=', $email)
            ->where('token', '=', $hashedToken)
            ->get();
        
        if (empty($resets)) {
            return false;
        }
        
        $reset = $resets[0];
        
        // Check if token is expired (valid for 1 hour)
        $createdAt = strtotime($reset['created_at']);
        if (time() - $createdAt > 3600) {
            return false;
        }
        
        // Update password
        $users = \Core\Database\DB::table('users')
            ->where('email', '=', $email)
            ->get();
        
        if (empty($users)) {
            return false;
        }
        
        $user = User::hydrate($users[0]);
        $user->update(['password' => $newPassword]);
        
        // Delete reset token
        \Core\Database\DB::table('password_resets')
            ->where('email', '=', $email)
            ->delete();
        
        return true;
    }
}
