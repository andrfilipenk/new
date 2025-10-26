<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #28a745; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f8f9fa; }
        .button { display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= htmlspecialchars($title ?? 'Notification') ?></h1>
        </div>
        <div class="content">
            <h2>Hello, <?= htmlspecialchars($name ?? 'User') ?>!</h2>
            <?= $message ?? '' ?>
            
            <?php if (isset($action_url) && isset($action_text)): ?>
            <p style="text-align: center;">
                <a href="<?= htmlspecialchars($action_url) ?>" class="button"><?= htmlspecialchars($action_text) ?></a>
            </p>
            <?php endif; ?>
        </div>
        <div class="footer">
            <p>&copy; <?= date('Y') ?> <?= $app_name ?? 'CRM-ERP Platform' ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
