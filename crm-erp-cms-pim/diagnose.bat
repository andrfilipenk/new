@echo off
echo ================================================
echo CRM Platform - Quick Diagnosis
echo ================================================
echo.

echo Checking Apache Virtual Host Configuration...
echo.
findstr /C:"crm.local" C:\xampp\apache\conf\extra\httpd-vhosts.conf
if %errorlevel% == 0 (
    echo [OK] Virtual host configuration found
) else (
    echo [WARNING] Virtual host NOT configured
    echo.
    echo SOLUTION: You need to configure virtual host
    echo See SIDEBAR_FIX_GUIDE.md for detailed instructions
)
echo.

echo Checking hosts file...
findstr /C:"crm.local" C:\Windows\System32\drivers\etc\hosts
if %errorlevel% == 0 (
    echo [OK] Hosts file entry found
) else (
    echo [WARNING] Hosts file entry NOT found
    echo.
    echo SOLUTION: Add this line to C:\Windows\System32\drivers\etc\hosts
    echo 127.0.0.1    crm.local
)
echo.

echo Checking mod_rewrite...
findstr /C:"LoadModule rewrite_module" C:\xampp\apache\conf\httpd.conf | findstr /V "#"
if %errorlevel% == 0 (
    echo [OK] mod_rewrite is enabled
) else (
    echo [WARNING] mod_rewrite might be disabled
)
echo.

echo ================================================
echo QUICK FIX INSTRUCTIONS:
echo ================================================
echo.
echo 1. Open hosts file as Administrator:
echo    notepad C:\Windows\System32\drivers\etc\hosts
echo.
echo 2. Add this line at the end:
echo    127.0.0.1    crm.local
echo.
echo 3. Restart Apache in XAMPP Control Panel
echo.
echo 4. Access the site at: http://crm.local/
echo.
echo OR see SIDEBAR_FIX_GUIDE.md for full instructions
echo.
pause
