<?php
// Start output buffering for content
ob_start();
?>

<div class="auth-header">
    <div class="auth-logo">🚀</div>
    <h1 class="auth-title">Create Account</h1>
    <p class="auth-subtitle">Join us and start managing your business</p>
</div>

<form method="POST" action="/register" id="registerForm">
    <!-- CSRF Token -->
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
    
    <!-- Name Field -->
    <div class="mb-3">
        <label for="name" class="form-label">Full Name</label>
        <input 
            type="text" 
            class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" 
            id="name" 
            name="name" 
            value="<?= htmlspecialchars($old['name'] ?? '') ?>"
            placeholder="Enter your full name"
            required
            autofocus
        >
        <?php if (isset($errors['name'])): ?>
            <div class="invalid-feedback">
                <?= htmlspecialchars($errors['name']) ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Email Field -->
    <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input 
            type="email" 
            class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
            id="email" 
            name="email" 
            value="<?= htmlspecialchars($old['email'] ?? '') ?>"
            placeholder="Enter your email"
            required
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
            placeholder="Create a strong password"
            required
        >
        <?php if (isset($errors['password'])): ?>
            <div class="invalid-feedback">
                <?= htmlspecialchars($errors['password']) ?>
            </div>
        <?php endif; ?>
        <small class="text-muted">Minimum 8 characters</small>
    </div>
    
    <!-- Password Confirmation Field -->
    <div class="mb-3">
        <label for="password_confirmation" class="form-label">Confirm Password</label>
        <input 
            type="password" 
            class="form-control" 
            id="password_confirmation" 
            name="password_confirmation" 
            placeholder="Confirm your password"
            required
        >
    </div>
    
    <!-- Submit Button -->
    <button type="submit" class="btn btn-primary mt-2">
        Create Account
    </button>
</form>

<div class="divider">
    <span>Already have an account?</span>
</div>

<div class="text-center">
    <a href="/login" class="auth-link">Sign in instead</a>
</div>

<?php
$content = ob_get_clean();

// Set page title
$title = 'Register - CRM-ERP-CMS-PIM Platform';

// Include guest layout
include __DIR__ . '/../layouts/guest.php';
