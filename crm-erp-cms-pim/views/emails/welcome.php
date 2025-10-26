<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .button { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to <?= $app_name ?? 'CRM-ERP Platform' ?></h1>
        </div>
        <div class="content">
            <h2>Hello, <?= htmlspecialchars($name ?? 'User') ?>!</h2>
            <p>Thank you for registering with us. We're excited to have you on board!</p>
            <p>Your account has been successfully created. You can now log in and start using our platform.</p>
            <?php if (isset($login_url)): ?>
            <p style="text-align: center;">
                <a href="<?= htmlspecialchars($login_url) ?>" class="button">Login to Your Account</a>
            </p>
            <?php endif; ?>
            <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
        </div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> <?= $app_name ?? 'CRM-ERP Platform' ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
