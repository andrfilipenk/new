<?php
// Start output buffering for content
ob_start();
?>

<div class="auth-header">
    <div class="auth-logo">🔐</div>
    <h1 class="auth-title">Welcome Back</h1>
    <p class="auth-subtitle">Sign in to your account to continue</p>
</div>

<form method="POST" action="/login" id="loginForm">
    <!-- CSRF Token -->
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
    
    <!-- Email Field -->
    <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input 
            type="email" 
            class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
            id="email" 
            name="email" 
            value="<?= htmlspecialchars($old_email ?? '') ?>"
            placeholder="Enter your email"
            required
            autofocus
        >
        <?php if (isset($errors['email'])): ?>
            <div class="invalid-feedback">
                <?= htmlspecialchars($errors['email']) ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Password Field -->
    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input 
            type="password" 
            class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" 
            id="password" 
            name="password" 
            placeholder="Enter your password"
            required
        >
        <?php if (isset($errors['password'])): ?>
            <div class="invalid-feedback">
                <?= htmlspecialchars($errors['password']) ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Remember Me & Forgot Password -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
            <input 
                type="checkbox" 
                class="form-check-input" 
                id="remember" 
                name="remember"
                value="1"
            >
            <label class="form-check-label" for="remember">
                Remember me
            </label>
        </div>
        <a href="/password/forgot" class="auth-link">Forgot Password?</a>
    </div>
    
    <!-- Submit Button -->
    <button type="submit" class="btn btn-primary">
        Sign In
    </button>
</form>

<div class="divider">
    <span>Don't have an account?</span>
</div>

<div class="text-center">
    <a href="/register" class="auth-link">Create an account</a>
</div>

<?php
$content = ob_get_clean();

// Set page title
$title = 'Login - CRM-ERP-CMS-PIM Platform';

// Include guest layout
include __DIR__ . '/../layouts/guest.php';
