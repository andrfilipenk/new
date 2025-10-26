<?php
// Start output buffering for content
ob_start();
?>

<div class="auth-header">
    <div class="auth-logo">🔑</div>
    <h1 class="auth-title">Forgot Password</h1>
    <p class="auth-subtitle">Enter your email to reset your password</p>
</div>

<form method="POST" action="/password/reset" id="forgotPasswordForm">
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
    
    <!-- Submit Button -->
    <button type="submit" class="btn btn-primary">
        Send Reset Link
    </button>
</form>

<div class="divider">
    <span>Remember your password?</span>
</div>

<div class="text-center">
    <a href="/login" class="auth-link">Back to login</a>
</div>

<?php
$content = ob_get_clean();

// Set page title
$title = 'Forgot Password - CRM-ERP-CMS-PIM Platform';

// Include guest layout
include __DIR__ . '/../layouts/guest.php';
