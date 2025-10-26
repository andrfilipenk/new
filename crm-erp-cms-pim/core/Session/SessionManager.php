<?php

namespace Core\Session;

use Core\Config\Config;

/**
 * Session Manager
 * 
 * Manages user sessions with security features
 */
class SessionManager
{
    /**
     * Session started flag
     * 
     * @var bool
     */
    protected static bool $started = false;

    /**
     * Start the session
     * 
     * @return void
     */
    public static function start(): void
    {
        if (self::$started) {
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        // Configure session
        $config = Config::file('security')['session'] ?? [];
        
        ini_set('session.cookie_httponly', $config['http_only'] ?? 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', $config['same_site'] ?? 'Lax');
        
        if ($config['secure'] ?? false) {
            ini_set('session.cookie_secure', 1);
        }

        session_name('app_session');
        session_start();
        self::$started = true;

        // Regenerate session ID periodically for security
        if (!self::has('_session_started_at')) {
            self::regenerate();
            self::set('_session_started_at', time());
        }
    }

    /**
     * Regenerate session ID
     * 
     * @param bool $deleteOld
     * @return void
     */
    public static function regenerate($deleteOld = true): void
    {
        if (!self::$started) {
            self::start();
        }

        session_regenerate_id($deleteOld);
    }

    /**
     * Get a session value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null): mixed
    {
        if (!self::$started) {
            self::start();
        }

        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session value
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        if (!self::$started) {
            self::start();
        }

        $_SESSION[$key] = $value;
    }

    /**
     * Check if session has a key
     * 
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        if (!self::$started) {
            self::start();
        }

        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session value
     * 
     * @param string $key
     * @return void
     */
    public static function forget(string $key): void
    {
        if (!self::$started) {
            self::start();
        }

        unset($_SESSION[$key]);
    }

    /**
     * Flush all session data
     * 
     * @return void
     */
    public static function flush(): void
    {
        if (!self::$started) {
            self::start();
        }

        $_SESSION = [];
    }

    /**
     * Flash data for next request
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function flash(string $key, mixed $value): void
    {
        if (!self::$started) {
            self::start();
        }
        
        // Initialize flash array if it doesn't exist
        if (!isset($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }
        
        // Store in flash array
        $_SESSION['flash'][$key] = $value;
        
        // Track flash key for aging
        self::push('_flash_keys', $key);
    }

    /**
     * Get flash data
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getFlash(string $key, $default = null): mixed
    {
        if (!self::$started) {
            self::start();
        }
        
        // Retrieve from flash array
        $value = $_SESSION['flash'][$key] ?? $default;
        
        // Delete immediately after retrieval
        if (isset($_SESSION['flash'][$key])) {
            unset($_SESSION['flash'][$key]);
        }
        
        return $value;
    }

    /**
     * Age flash data (call at end of request)
     * 
     * @return void
     */
    public static function ageFlashData(): void
    {
        if (!self::$started) {
            self::start();
        }
        
        // Get tracked flash keys
        $flashKeys = self::get('_flash_keys', []);
        
        // Delete each flash data item from flash array
        foreach ($flashKeys as $key) {
            if (isset($_SESSION['flash'][$key])) {
                unset($_SESSION['flash'][$key]);
            }
        }
        
        // Clean up flash array if empty
        if (isset($_SESSION['flash']) && empty($_SESSION['flash'])) {
            unset($_SESSION['flash']);
        }
        
        // Clear flash keys tracker
        self::forget('_flash_keys');
    }

    /**
     * Push a value onto an array session value
     * 
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function push(string $key, mixed $value): void
    {
        $array = self::get($key, []);
        $array[] = $value;
        self::set($key, $array);
    }

    /**
     * Get session ID
     * 
     * @return string
     */
    public static function getId(): string
    {
        if (!self::$started) {
            self::start();
        }

        return session_id();
    }

    /**
     * Destroy the session
     * 
     * @return void
     */
    public static function destroy(): void
    {
        if (!self::$started) {
            self::start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        self::$started = false;
    }

    /**
     * Get all session data
     * 
     * @return array
     */
    public static function all(): array
    {
        if (!self::$started) {
            self::start();
        }

        return $_SESSION;
    }
}
