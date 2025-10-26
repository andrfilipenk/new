<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .button { display: inline-block; padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .code { font-size: 24px; font-weight: bold; letter-spacing: 3px; padding: 15px; background: white; border: 2px dashed #dc3545; text-align: center; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Password Reset Request</h1>
        </div>
        <div class="content">
            <h2>Hello, <?= htmlspecialchars($name ?? 'User') ?>!</h2>
            <p>We received a request to reset your password. If you didn't make this request, please ignore this email.</p>
            
            <?php if (isset($reset_link)): ?>
            <p>Click the button below to reset your password:</p>
            <p style="text-align: center;">
                <a href="<?= htmlspecialchars($reset_link) ?>" class="button">Reset Password</a>
            </p>
            <p style="font-size: 12px; color: #666;">Or copy and paste this link into your browser:<br>
            <?= htmlspecialchars($reset_link) ?></p>
            <?php endif; ?>
            
            <?php if (isset($reset_code)): ?>
            <p>Alternatively, use this reset code:</p>
            <div class="code"><?= htmlspecialchars($reset_code) ?></div>
            <?php endif; ?>
            
            <div class="warning">
                <strong>⚠️ Security Notice:</strong> This link/code will expire in <?= $expiry_hours ?? 24 ?> hours for security reasons.
            </div>
            
            <p>If you didn't request a password reset, please contact our support team immediately.</p>
        </div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> <?= $app_name ?? 'CRM-ERP Platform' ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
