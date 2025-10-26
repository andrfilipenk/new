# Sidebar Navigation Fix Guide

## Problem
Sidebar links not working even when logged in as super admin.

## Diagnosis Steps

### Step 1: Access the Route Diagnostics Page
Navigate to: **http://crm.local/test_routes.php** (or http://localhost/crm-erp-cms-pim/public/test_routes.php)

This page will show:
- Current configuration
- All registered routes
- Apache mod_rewrite status

### Step 2: Check Your Access URL

**IMPORTANT**: You must access the application via the correct URL:

✅ **CORRECT**: `http://crm.local/`  
❌ **WRONG**: `http://localhost/crm-erp-cms-pim/public/`

## Solution 1: Configure Virtual Host (Recommended)

### Windows (XAMPP) Setup

1. **Edit hosts file** (as Administrator):
   - File location: `C:\Windows\System32\drivers\etc\hosts`
   - Add this line:
   ```
   127.0.0.1    crm.local
   ```

2. **Configure Apache Virtual Host**:
   - Open: `C:\xampp\apache\conf\extra\httpd-vhosts.conf`
   - Add this configuration:
   ```apache
   <VirtualHost *:80>
       ServerName crm.local
       DocumentRoot "C:/xampp/htdocs/crm-erp-cms-pim/public"
       
       <Directory "C:/xampp/htdocs/crm-erp-cms-pim/public">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
       
       ErrorLog "logs/crm-error.log"
       CustomLog "logs/crm-access.log" common
   </VirtualHost>
   ```

3. **Enable Virtual Hosts in Apache**:
   - Open: `C:\xampp\apache\conf\httpd.conf`
   - Find and uncomment (remove #):
   ```apache
   Include conf/extra/httpd-vhosts.conf
   ```

4. **Restart Apache** via XAMPP Control Panel

5. **Access**: `http://crm.local/`

## Solution 2: Update Configuration for Localhost Access (Alternative)

If you prefer using localhost without virtual host:

1. **Update `config/app.php`**:
   ```php
   'url' => env('APP_URL', 'http://localhost/crm-erp-cms-pim/public'),
   ```

2. **Restart Apache**

3. **Access**: `http://localhost/crm-erp-cms-pim/public/`

## Verify mod_rewrite is Enabled

1. Open: `C:\xampp\apache\conf\httpd.conf`
2. Find this line and ensure it's NOT commented:
   ```apache
   LoadModule rewrite_module modules/mod_rewrite.so
   ```
3. Restart Apache

## Common Issues & Fixes

### Issue 1: "Route not found" errors
**Cause**: Accessing via wrong URL or mod_rewrite disabled  
**Fix**: 
- Use correct URL (http://crm.local/)
- Enable mod_rewrite
- Check .htaccess exists in /public directory

### Issue 2: Links redirect to wrong pages
**Cause**: BASE_URL mismatch  
**Fix**: Update config/app.php 'url' to match your access URL

### Issue 3: Admin pages show 403 Forbidden
**Cause**: Not logged in as admin or role middleware issue  
**Fix**: 
- Verify you're logged in with admin role
- Check database: `SELECT * FROM role_user WHERE user_id = YOUR_USER_ID;`
- Should have role_id = 1 (admin role)

### Issue 4: Static files (CSS/JS) not loading
**Cause**: Wrong base URL  
**Fix**: Ensure app.url matches your access URL

## Testing Checklist

After applying the fix:

- [ ] Access http://crm.local/ (or your configured URL)
- [ ] Login with admin credentials
- [ ] Click Dashboard - should load
- [ ] Click CRM → Leads - should load
- [ ] Click ERP → Sales - should load
- [ ] Click Admin → Users - should load
- [ ] No "Route not found" errors

## Debug Information

If issues persist, check these logs:

1. **Apache Error Log**:
   - Location: `C:\xampp\apache\logs\error.log`

2. **Application Log**:
   - Location: `storage/logs/app.log`

3. **Browser Console**:
   - Press F12 → Console tab
   - Check for JavaScript errors

## Quick Test Commands

Run these in PowerShell from project root:

```powershell
# Check if routes file is loaded
Get-Content app\routes.php | Select-String "admin/users"

# Check .htaccess exists
Test-Path public\.htaccess

# View Apache modules
Get-Content C:\xampp\apache\conf\httpd.conf | Select-String "mod_rewrite"
```

## Expected Behavior After Fix

1. All sidebar links navigate successfully
2. Dashboard displays statistics
3. Admin links accessible (with admin role)
4. No 404 errors
5. Clean URLs without /public/index.php

## Still Not Working?

If sidebar links still don't work:

1. **Clear browser cache** (Ctrl+Shift+Delete)
2. **Try incognito/private mode**
3. **Check session is active**:
   - Add this to any view temporarily:
   ```php
   <?php var_dump(auth()->user()); ?>
   ```
   - Should show user object, not null

4. **Verify authentication**:
   - Go to: http://crm.local/dashboard
   - Should redirect to login if not authenticated
   - Should show dashboard if authenticated

## Contact Points

If you need to verify specific configurations:

- Routes: `app/routes.php`
- Config: `config/app.php`
- .htaccess: `public/.htaccess`
- Entry point: `public/index.php`
- Auth middleware: `core/Middleware/AuthMiddleware.php`
