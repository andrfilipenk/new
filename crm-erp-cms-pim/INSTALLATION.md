# Installation & Setup Guide

## Quick Start (5 Minutes)

### Prerequisites Check

```bash
# Check PHP version (requires 8.0+)
php -v

# Check required extensions
php -m | grep -E 'pdo|pdo_mysql|gd|mbstring|openssl'

# Check Apache mod_rewrite
apache2ctl -M | grep rewrite
```

### Step 1: Database Setup

```sql
-- Create database
CREATE DATABASE crm_erp_cms_pim CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (optional)
CREATE USER 'crm_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON crm_erp_cms_pim.* TO 'crm_user'@'localhost';
FLUSH PRIVILEGES;
```

### Step 2: Configuration

```bash
# Copy example config
cp config/database.example.php config/database.php
cp config/app.example.php config/app.php

# Edit database config
nano config/database.php
```

Update with your database credentials:

```php
return [
    'host' => 'localhost',
    'database' => 'crm_erp_cms_pim',
    'username' => 'crm_user',
    'password' => 'secure_password',
    'charset' => 'utf8mb4',
    'port' => 3306
];
```

### Step 3: File Permissions

```bash
# Set directory permissions
chmod -R 755 storage/
chmod -R 755 storage/cache/
chmod -R 755 storage/logs/
chmod -R 755 storage/uploads/

# Set ownership (if needed)
chown -R www-data:www-data storage/
```

### Step 4: Initialize Database

```bash
# Run all migrations (creates 45 tables)
php cli/migrate.php

# Seed initial data (roles, permissions, demo users)
php cli/seed.php
```

### Step 5: Web Server Configuration

#### Apache Virtual Host

Create `/etc/apache2/sites-available/crm.conf`:

```apache
<VirtualHost *:80>
    ServerName crm.local
    ServerAlias www.crm.local
    DocumentRoot /var/www/crm-erp-cms-pim/public
    
    <Directory /var/www/crm-erp-cms-pim/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/crm_error.log
    CustomLog ${APACHE_LOG_DIR}/crm_access.log combined
</VirtualHost>
```

Enable site:

```bash
sudo a2ensite crm.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

Add to `/etc/hosts`:

```
127.0.0.1  crm.local
```

#### Nginx Configuration

Create `/etc/nginx/sites-available/crm`:

```nginx
server {
    listen 80;
    server_name crm.local;
    root /var/www/crm-erp-cms-pim/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

Enable site:

```bash
sudo ln -s /etc/nginx/sites-available/crm /etc/nginx/sites-enabled/
sudo systemctl reload nginx
```

### Step 6: Access Application

Visit `http://crm.local` and login with:

- **Email:** admin@example.com
- **Password:** password

## Demo User Accounts

| Email | Password | Role | Permissions |
|-------|----------|------|-------------|
| admin@example.com | password | Super Admin | All permissions |
| manager@example.com | password | Manager | Most permissions |
| user@example.com | password | User | Limited permissions |

## Post-Installation Tasks

### 1. Change Default Passwords

```php
// In PHP console or create a script
use App\Models\User;
use Core\Security\PasswordHasher;

$user = User::find(1);
$user->password = PasswordHasher::make('new_secure_password');
$user->save();
```

### 2. Configure Email (Optional)

Edit `config/mail.php`:

```php
return [
    'driver' => 'smtp',
    'host' => 'smtp.mailtrap.io',
    'port' => 2525,
    'username' => 'your_username',
    'password' => 'your_password',
    'encryption' => 'tls',
    'from' => [
        'address' => 'noreply@example.com',
        'name' => 'CRM System'
    ]
];
```

### 3. Set Up Cron Jobs

```bash
# Edit crontab
crontab -e

# Add scheduled tasks
# Clear cache daily
0 0 * * * cd /var/www/crm-erp-cms-pim && php cli/cache.php clear

# Generate reports daily
0 1 * * * cd /var/www/crm-erp-cms-pim && php cli/reports.php generate
```

### 4. Configure File Upload Limits

Edit `php.ini`:

```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
memory_limit = 256M
```

Restart Apache/Nginx:

```bash
sudo systemctl restart apache2
# or
sudo systemctl restart nginx
sudo systemctl restart php8.0-fpm
```

### 5. Enable Production Mode

Edit `config/app.php`:

```php
return [
    'debug' => false,  // Disable debug mode
    'environment' => 'production',
    'timezone' => 'UTC',
    'locale' => 'en'
];
```

### 6. Set Up SSL (Production)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# Get SSL certificate
sudo certbot --apache -d crm.yourdomain.com

# Auto-renewal
sudo certbot renew --dry-run
```

## Troubleshooting

### Issue: Database Connection Failed

**Solution:**
- Check database credentials in `config/database.php`
- Ensure MySQL is running: `sudo systemctl status mysql`
- Verify user has correct permissions

### Issue: 500 Internal Server Error

**Solution:**
- Check Apache error logs: `tail -f /var/log/apache2/error.log`
- Verify file permissions on `storage/` directory
- Ensure `.htaccess` is present in `public/` directory
- Check `mod_rewrite` is enabled

### Issue: Assets Not Loading

**Solution:**
- Clear browser cache
- Check file paths in view templates
- Verify `public/` directory is web root
- Check file permissions

### Issue: Migration Errors

**Solution:**
- Drop database and recreate: `DROP DATABASE crm_erp_cms_pim; CREATE DATABASE crm_erp_cms_pim;`
- Run migrations again: `php cli/migrate.php`
- Check database user permissions

### Issue: Cannot Login

**Solution:**
- Reset admin password via database:
```sql
UPDATE users SET password = '$argon2id$v=19$m=65536,t=4,p=1$...' WHERE email = 'admin@example.com';
```
- Or reseed database: `php cli/seed.php`

### Issue: Slow Performance

**Solution:**
- Enable opcache in `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
```
- Clear application cache: `php cli/cache.php clear`
- Add database indexes if needed
- Enable query caching

## Backup & Restore

### Database Backup

```bash
# Create backup
mysqldump -u crm_user -p crm_erp_cms_pim > backup_$(date +%Y%m%d).sql

# Compress backup
gzip backup_$(date +%Y%m%d).sql
```

### Restore Database

```bash
# Decompress
gunzip backup_20240101.sql.gz

# Restore
mysql -u crm_user -p crm_erp_cms_pim < backup_20240101.sql
```

### File Backup

```bash
# Backup uploads directory
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz storage/uploads/

# Full application backup
tar -czf crm_full_backup_$(date +%Y%m%d).tar.gz \
    --exclude='storage/cache/*' \
    --exclude='storage/logs/*' \
    /var/www/crm-erp-cms-pim/
```

## Updating

### Apply Updates

```bash
# Backup first!
mysqldump -u crm_user -p crm_erp_cms_pim > pre_update_backup.sql

# Pull updates (if using git)
git pull origin main

# Run new migrations
php cli/migrate.php

# Clear cache
php cli/cache.php clear
```

## Security Checklist

- [ ] Changed all default passwords
- [ ] Disabled debug mode in production
- [ ] Set proper file permissions (755 for directories, 644 for files)
- [ ] Configured SSL certificate
- [ ] Set up firewall rules
- [ ] Configured rate limiting
- [ ] Regular security updates
- [ ] Database backups configured
- [ ] Error logs monitored
- [ ] Strong password policy enforced

## Performance Optimization

### 1. Enable OPcache

```bash
# Check if enabled
php -i | grep opcache.enable

# If not enabled, add to php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

### 2. Database Optimization

```sql
-- Add indexes for frequently queried columns
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_products_sku ON pim_products(sku);

-- Optimize tables
OPTIMIZE TABLE users;
OPTIMIZE TABLE pim_products;
```

### 3. Enable Caching

```php
// In controllers
use Core\Cache\Cache;

$products = Cache::remember('products.all', 3600, function() {
    return Product::all();
});
```

## Support & Resources

- **Documentation:** Check `/docs` directory
- **API Reference:** Visit `/api/docs` (when implemented)
- **Community Forum:** (setup as needed)
- **Issue Tracker:** GitHub Issues

## Next Steps

1. ✅ Complete installation
2. ✅ Login and explore dashboard
3. Configure company settings
4. Import existing data (if any)
5. Train users on the system
6. Set up regular backups
7. Monitor system performance
8. Plan feature enhancements

---

**Installation Support:** support@example.com
