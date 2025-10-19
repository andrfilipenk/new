# EAV Phase 6: Deployment Guide
## Production Deployment Instructions

**Version:** 1.0  
**Date:** October 19, 2025

---

## Pre-Deployment Checklist

- [ ] PHP 7.4+ installed on server
- [ ] MySQL 5.7+ installed and accessible
- [ ] EAV Phases 1-5 successfully deployed
- [ ] Web server (Apache/Nginx) configured
- [ ] SSL certificate installed (required for API)
- [ ] Backup of current database created
- [ ] Backup of current codebase created

---

## Step 1: Database Migration

### 1.1 Backup Current Database

```bash
# Create backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql
```

### 1.2 Run Migrations

```bash
cd c:\xampp\htdocs\new
php public/migrate.php
```

### 1.3 Verify Tables Created

```sql
-- Connect to your database
USE your_database_name;

-- Check new tables
SHOW TABLES LIKE 'eav_%';

-- Expected output should include:
-- eav_entity_versions
-- eav_audit_log
-- eav_api_tokens
-- eav_import_jobs
-- eav_export_jobs
-- eav_reports
-- eav_webhooks
-- eav_user_permissions
```

### 1.4 Create Indexes (if not auto-created)

```sql
-- Ensure critical indexes exist
ALTER TABLE eav_audit_log ADD INDEX idx_event_type (event_type);
ALTER TABLE eav_audit_log ADD INDEX idx_created_at (created_at);
ALTER TABLE eav_api_tokens ADD INDEX idx_token_hash (token_hash);
ALTER TABLE eav_entity_versions ADD INDEX idx_entity_version (entity_id, version_number);
```

---

## Step 2: Deploy Code Files

### 2.1 Copy Phase 6 Files

Ensure these directories are deployed:

```
app/Eav/Admin/
├── Controller/
├── Service/
├── Middleware/
├── Models/
├── Provider/
├── config.php
└── QUICKSTART.md
```

### 2.2 Set File Permissions

```bash
# Linux/Unix
chmod -R 755 app/Eav/Admin
chmod 644 app/Eav/Admin/config.php

# Ensure writable directories
chmod -R 777 /tmp/eav_exports
chmod -R 777 /tmp/eav_imports
chmod -R 777 /tmp/eav_rate_limits
```

---

## Step 3: Configuration

### 3.1 Update Main Configuration

Edit `config.php` to include Phase 6 configuration:

```php
// config.php
return array_merge(
    [
        // Existing configuration
        'database' => [
            'host' => 'localhost',
            'database' => 'your_database',
            'username' => 'your_user',
            'password' => 'your_password'
        ],
        // ... other config
    ],
    require __DIR__ . '/app/Eav/Admin/config.php'
);
```

### 3.2 Configure Environment-Specific Settings

**Production Settings:**

```php
// app/Eav/Admin/config.php
'eav_admin' => [
    'api' => [
        'rate_limit_enabled' => true,
        'rate_limit_per_minute' => 100,
        'cors_enabled' => false,         // Set allowed origins instead
        'allowed_origins' => [
            'https://yourdomain.com',
            'https://app.yourdomain.com'
        ],
    ],
    'audit' => [
        'enabled' => true,
        'log_read_operations' => false,  // Disable in production for performance
        'retention_days' => 730,         // 2 years
    ],
    'versioning' => [
        'enabled' => true,
        'retention_days' => 365,
        'auto_cleanup' => true,
    ],
    'security' => [
        'require_authentication' => true,
        'enable_rbac' => true,
    ],
]
```

### 3.3 Configure Temporary Directories

```php
// Use system temp in development, dedicated paths in production
'import' => [
    'temp_directory' => '/var/www/storage/eav/imports'  // Production path
],
'export' => [
    'temp_directory' => '/var/www/storage/eav/exports'  // Production path
],
```

---

## Step 4: Register Service Provider

### 4.1 Update Application Bootstrap

Edit `bootstrap.php` to register the AdminServiceProvider:

```php
// bootstrap.php
use Eav\Admin\Provider\AdminServiceProvider;

// ... existing providers

// Register Phase 6 Admin Service Provider
$container->registerProvider(new AdminServiceProvider());
```

### 4.2 Verify Service Registration

```php
// Test service availability
try {
    $adminService = $container->get(\Eav\Admin\Service\AdminService::class);
    $apiService = $container->get(\Eav\Admin\Service\APIService::class);
    echo "Services registered successfully!";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

---

## Step 5: Configure Routes

### 5.1 Add API Routes

Create or update your route configuration:

```php
// routes/api.php or in your routing configuration

use Eav\Admin\Controller\EntityTypeApiController;
use Eav\Admin\Controller\EntityApiController;
use Eav\Admin\Middleware\ApiAuthenticationMiddleware;
use Eav\Admin\Middleware\RateLimitMiddleware;

// Entity Type API Routes
$router->group(['prefix' => 'api/v1/eav', 'middleware' => [
    new ApiAuthenticationMiddleware(),
    new RateLimitMiddleware('read')
]], function($router) {
    
    // Entity Types
    $router->get('/entity-types', [EntityTypeApiController::class, 'index']);
    $router->get('/entity-types/{code}', [EntityTypeApiController::class, 'show']);
    $router->post('/entity-types', [EntityTypeApiController::class, 'store']);
    $router->put('/entity-types/{code}', [EntityTypeApiController::class, 'update']);
    $router->delete('/entity-types/{code}', [EntityTypeApiController::class, 'destroy']);
    $router->get('/entity-types/{code}/attributes', [EntityTypeApiController::class, 'attributes']);
    $router->get('/entity-types/{code}/stats', [EntityTypeApiController::class, 'stats']);
    
    // Entities
    $router->get('/entities/{entityType}', [EntityApiController::class, 'index']);
    $router->get('/entities/{entityType}/{id}', [EntityApiController::class, 'show']);
    $router->post('/entities/{entityType}', [EntityApiController::class, 'store']);
    $router->put('/entities/{entityType}/{id}', [EntityApiController::class, 'update']);
    $router->delete('/entities/{entityType}/{id}', [EntityApiController::class, 'destroy']);
    $router->post('/entities/{entityType}/search', [EntityApiController::class, 'search']);
    $router->post('/entities/{entityType}/bulk', [EntityApiController::class, 'bulkCreate']);
    $router->put('/entities/{entityType}/bulk', [EntityApiController::class, 'bulkUpdate']);
});
```

---

## Step 6: Security Setup

### 6.1 Configure HTTPS

Ensure all API endpoints use HTTPS:

```apache
# Apache .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^api/.* https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

```nginx
# Nginx configuration
server {
    listen 80;
    server_name your_domain.com;
    
    location /api/ {
        return 301 https://$server_name$request_uri;
    }
}
```

### 6.2 Set Up Initial Admin User

```php
// Create admin user and permissions
use User\Model\User;
use Eav\Admin\Models\UserPermission;

// Create/get admin user
$admin = User::where('email', 'admin@yourdomain.com')->first();

// Create super admin permission
$permission = new UserPermission();
$permission->user_id = $admin->id;
$permission->role = UserPermission::ROLE_SUPER_ADMIN;
$permission->permissions = [
    'create' => true,
    'read' => true,
    'update' => true,
    'delete' => true,
    'export' => true,
    'bulk_operations' => true
];
$permission->created_at = date('Y-m-d H:i:s');
$permission->updated_at = date('Y-m-d H:i:s');
$permission->save();
```

### 6.3 Generate Initial API Token

```php
use Eav\Admin\Models\ApiToken;

// Generate token for admin
$result = ApiToken::generate(
    $admin->id,
    'Production API Token',
    ['*'],  // Full access
    null    // Never expires (or set expiry days)
);

// Save this token securely!
echo "API Token: " . $result['token'];
```

---

## Step 7: Performance Optimization

### 7.1 Enable Caching

```php
// config.php
'cache' => [
    'enabled' => true,
    'driver' => 'redis',  // or 'memcached'
    'prefix' => 'eav_'
],
```

### 7.2 Database Optimization

```sql
-- Optimize tables
OPTIMIZE TABLE eav_entity_versions;
OPTIMIZE TABLE eav_audit_log;
OPTIMIZE TABLE eav_api_tokens;

-- Analyze tables
ANALYZE TABLE eav_entity_versions;
ANALYZE TABLE eav_audit_log;
```

### 7.3 Set Up Cleanup Cron Jobs

```cron
# Crontab entries

# Clean old audit logs (daily at 2 AM)
0 2 * * * php /path/to/project/cleanup_audit_logs.php

# Clean old entity versions (weekly on Sunday at 3 AM)
0 3 * * 0 php /path/to/project/cleanup_versions.php

# Clean rate limit files (hourly)
0 * * * * php /path/to/project/cleanup_rate_limits.php
```

Create cleanup scripts:

```php
// cleanup_audit_logs.php
<?php
require 'bootstrap.php';

$auditService = $container->get(\Eav\Admin\Service\AuditLoggingService::class);
$deleted = $auditService->cleanOldLogs(730); // 2 years retention
echo "Deleted {$deleted} old audit log entries\n";
```

```php
// cleanup_versions.php
<?php
require 'bootstrap.php';

$versioningService = $container->get(\Eav\Admin\Service\VersioningService::class);
$deleted = $versioningService->cleanOldVersions();
echo "Deleted {$deleted} old version records\n";
```

```php
// cleanup_rate_limits.php
<?php
require 'bootstrap.php';

$storagePath = sys_get_temp_dir() . '/eav_rate_limits';
$deleted = \Eav\Admin\Middleware\RateLimitMiddleware::cleanup($storagePath);
echo "Cleaned up {$deleted} expired rate limit files\n";
```

---

## Step 8: Testing

### 8.1 Test API Endpoints

```bash
# Test health
curl -X GET https://your-domain.com/api/v1/eav/entity-types \
  -H "Authorization: Bearer YOUR_TOKEN"

# Should return 200 with entity types list
```

### 8.2 Test Authentication

```bash
# Test without token (should fail)
curl -X GET https://your-domain.com/api/v1/eav/entity-types

# Should return 401 Unauthorized
```

### 8.3 Test Rate Limiting

```bash
# Make 101 requests rapidly (should hit rate limit)
for i in {1..101}; do
  curl -X GET https://your-domain.com/api/v1/eav/entity-types \
    -H "Authorization: Bearer YOUR_TOKEN"
done

# 101st request should return 429 Too Many Requests
```

---

## Step 9: Monitoring Setup

### 9.1 Enable Error Logging

```php
// config.php
'logging' => [
    'enabled' => true,
    'level' => 'error',  // error, warning, info, debug
    'path' => '/var/log/eav/app.log'
],
```

### 9.2 Monitor Audit Logs

```sql
-- Check recent failed operations
SELECT * FROM eav_audit_log 
WHERE response_status >= 400 
ORDER BY created_at DESC 
LIMIT 100;

-- Check most active users
SELECT user_id, COUNT(*) as request_count
FROM eav_audit_log
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY user_id
ORDER BY request_count DESC;
```

### 9.3 Monitor Performance

```sql
-- Check slow operations
SELECT event_type, AVG(execution_time) as avg_time, MAX(execution_time) as max_time
FROM eav_audit_log
WHERE execution_time IS NOT NULL
GROUP BY event_type
ORDER BY avg_time DESC;
```

---

## Step 10: Rollback Plan

### 10.1 If Deployment Fails

```bash
# 1. Restore database backup
mysql -u username -p database_name < backup_YYYYMMDD_HHMMSS.sql

# 2. Remove Phase 6 files
rm -rf app/Eav/Admin/

# 3. Restore previous code from backup
cp -r backup/app/Eav/ app/

# 4. Clear cache
rm -rf cache/*

# 5. Restart web server
systemctl restart apache2  # or nginx
```

### 10.2 Partial Rollback

If only specific features are problematic:

```php
// Disable specific features in config
'eav_admin' => [
    'versioning' => [
        'enabled' => false,  // Disable versioning
    ],
    'audit' => [
        'enabled' => false,  // Disable audit logging
    ],
]
```

---

## Post-Deployment Verification

### Checklist

- [ ] All 8 database tables created successfully
- [ ] API endpoints respond with 200/201 status codes
- [ ] Authentication requires valid token
- [ ] Rate limiting enforces limits
- [ ] Audit logs are being created
- [ ] Entity versioning works correctly
- [ ] Import/Export functionality works
- [ ] No errors in application logs
- [ ] Performance is acceptable (< 500ms response times)
- [ ] Cleanup cron jobs are scheduled

---

## Support & Maintenance

### Regular Maintenance Tasks

1. **Weekly**: Review audit logs for suspicious activity
2. **Monthly**: Check database growth and optimize if needed
3. **Quarterly**: Review and revoke unused API tokens
4. **Annually**: Update retention policies and clean old data

### Common Issues

**Issue: High database growth**
- Solution: Reduce audit log retention or disable read operation logging

**Issue: Slow API responses**
- Solution: Add database indexes, enable query caching, use Redis for rate limiting

**Issue: Rate limit files accumulating**
- Solution: Ensure cleanup cron job is running

---

**Deployment Completed Successfully!**

For support, refer to:
- QUICKSTART.md - Quick start guide
- PHASE6_IMPLEMENTATION_PROGRESS.md - Implementation details
- API_REFERENCE.md - API documentation

---

**Last Updated:** October 19, 2025
