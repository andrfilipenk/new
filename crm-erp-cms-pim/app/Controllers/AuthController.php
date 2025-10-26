<?php

namespace App\Controllers;

use App\Models\User;
use Core\Request;
use Core\Response;
use Core\Auth\Auth;
use Core\Session\SessionManager;
use Core\Validation\ValidationException;

/**
 * Authentication Controller
 * 
 * Handles user authentication operations
 */
class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function showLogin(Request $request): Response
    {
        return $this->view('auth.login', [
            'csrf_token' => SessionManager::get('csrf_token'),
            'old_email' => SessionManager::get('old_email', ''),
            'errors' => SessionManager::get('errors', []),
        ]);
    }
    
    /**
     * Process login request
     */
    public function login(Request $request): Response
    {
        try {
            // Validate input
            $data = $this->validate($request, [
                'email' => 'required|email',
                'password' => 'required|min:6',
            ], [
                'email.required' => 'Email is required',
                'email.email' => 'Email must be valid',
                'password.required' => 'Password is required',
                'password.min' => 'Password must be at least 6 characters',
            ]);
            
            $remember = $request->input('remember', false);
            
            // Attempt authentication
            if (Auth::attempt(['email' => $data['email'], 'password' => $data['password']], $remember)) {
                // Check for intended URL
                $intendedUrl = SessionManager::get('intended_url', '/dashboard');
                SessionManager::forget('intended_url');
                
                SessionManager::flash('success', 'Login successful! Welcome back.');
                return $this->redirect($intendedUrl);
            }
            
            // Authentication failed
            SessionManager::flash('error', 'Invalid credentials. Please try again.');
            SessionManager::set('old_email', $data['email']);
            
            return $this->redirect('/login');
            
        } catch (ValidationException $e) {
            SessionManager::set('errors', $e->errors());
            SessionManager::set('old_email', $request->input('email', ''));
            return $this->redirect('/login');
        }
    }
    
    /**
     * Show registration form
     */
    public function showRegister(Request $request): Response
    {
        return $this->view('auth.register', [
            'csrf_token' => SessionManager::get('csrf_token'),
            'old' => [
                'name' => SessionManager::get('old_name', ''),
                'email' => SessionManager::get('old_email', ''),
            ],
            'errors' => SessionManager::get('errors', []),
        ]);
    }
    
    /**
     * Process registration request
     */
    public function register(Request $request): Response
    {
        try {
            // Validate input
            $data = $this->validate($request, [
                'name' => 'required|min:3|max:100',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8|confirmed',
            ], [
                'name.required' => 'Name is required',
                'name.min' => 'Name must be at least 3 characters',
                'name.max' => 'Name cannot exceed 100 characters',
                'email.required' => 'Email is required',
                'email.email' => 'Email must be valid',
                'email.unique' => 'Email is already registered',
                'password.required' => 'Password is required',
                'password.min' => 'Password must be at least 8 characters',
                'password.confirmed' => 'Password confirmation does not match',
            ]);
            
            // Create user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => 1,
            ]);
            
            // Auto-login
            Auth::login($user);
            
            SessionManager::flash('success', 'Registration successful! Welcome to the platform.');
            return $this->redirect('/dashboard');
            
        } catch (ValidationException $e) {
            SessionManager::set('errors', $e->errors());
            SessionManager::set('old_name', $request->input('name', ''));
            SessionManager::set('old_email', $request->input('email', ''));
            return $this->redirect('/register');
        }
    }
    
    /**
     * Process logout request
     */
    public function logout(Request $request): Response
    {
        Auth::logout();
        
        SessionManager::flash('success', 'You have been logged out successfully.');
        return $this->redirect('/login');
    }
    
    /**
     * Show forgot password form
     */
    public function showForgotPassword(Request $request): Response
    {
        return $this->view('auth.forgot-password', [
            'csrf_token' => SessionManager::get('csrf_token'),
            'errors' => SessionManager::get('errors', []),
        ]);
    }
    
    /**
     * Process password reset request
     */
    public function resetPassword(Request $request): Response
    {
        try {
            // Validate input
            $data = $this->validate($request, [
                'email' => 'required|email|exists:users,email',
            ], [
                'email.required' => 'Email is required',
                'email.email' => 'Email must be valid',
                'email.exists' => 'Email not found in our records',
            ]);
            
            // Generate reset token
            $token = Auth::createPasswordResetToken($data['email']);
            
            if ($token) {
                // In production, send email with reset link
                // For now, just flash a success message
                SessionManager::flash('success', 'Password reset instructions have been sent to your email.');
            } else {
                SessionManager::flash('error', 'Failed to create password reset token.');
            }
            
            return $this->redirect('/password/forgot');
            
        } catch (ValidationException $e) {
            SessionManager::set('errors', $e->errors());
            return $this->redirect('/password/forgot');
        }
    }
}
