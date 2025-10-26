<?php

/**
 * Global Helper Functions
 * 
 * Convenient functions for common operations
 */

if (!function_exists('env')) {
    /**
     * Get environment variable
     */
    function env(string $key, $default = null) {
        return Core\Config\Config::env($key, $default);
    }
}

if (!function_exists('config')) {
    /**
     * Get configuration value
     */
    function config(string $key, $default = null) {
        return Core\Config\Config::get($key, $default);
    }
}

if (!function_exists('session')) {
    /**
     * Get/set session value
     */
    function session(?string $key = null, $default = null) {
        if ($key === null) {
            return Core\Session\SessionManager::all();
        }
        return Core\Session\SessionManager::get($key, $default);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get CSRF token
     */
    function csrf_token(): string {
        return Core\Security\SecurityManager::getCsrfToken();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate CSRF input field
     */
    function csrf_field(): string {
        $token = csrf_token();
        return '<input type="hidden" name="_token" value="' . $token . '">';
    }
}

if (!function_exists('old')) {
    /**
     * Get old input value
     */
    function old(string $key, $default = null) {
        return Core\Session\SessionManager::get('_old_input.' . $key, $default);
    }
}

if (!function_exists('redirect')) {
    /**
     * Create redirect response
     */
    function redirect(string $url, int $status = 302): Core\Response {
        return Core\Response::redirect($url, $status);
    }
}

if (!function_exists('back')) {
    /**
     * Redirect back
     */
    function back(): Core\Response {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return redirect($referer);
    }
}

if (!function_exists('response')) {
    /**
     * Create response
     */
    function response($content = '', int $status = 200): Core\Response {
        if (is_array($content)) {
            return Core\Response::json($content, $status);
        }
        return Core\Response::html($content, $status);
    }
}

if (!function_exists('view')) {
    /**
     * Render view using View Engine
     */
    function view(string $template, array $data = []): Core\Response {
        return Core\View\ViewEngine::render($template, $data);
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML
     */
    function e(string $value): string {
        return Core\Security\SecurityManager::escape($value);
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and die
     */
    function dd(...$vars): void {
        echo '<pre style="background:#f8f9fa;padding:20px;border:1px solid #dee2e6;margin:10px;">';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        die(1);
    }
}

if (!function_exists('dump')) {
    /**
     * Dump variables
     */
    function dump(...$vars): void {
        echo '<pre style="background:#f8f9fa;padding:20px;border:1px solid #dee2e6;margin:10px;">';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
    }
}

if (!function_exists('logger')) {
    /**
     * Log message
     */
    function logger(?string $message = null, string $level = 'info', array $context = []) {
        if ($message === null) {
            return new class {
                public function debug($msg, $ctx = []) { Core\Logger\Logger::debug($msg, $ctx); }
                public function info($msg, $ctx = []) { Core\Logger\Logger::info($msg, $ctx); }
                public function warning($msg, $ctx = []) { Core\Logger\Logger::warning($msg, $ctx); }
                public function error($msg, $ctx = []) { Core\Logger\Logger::error($msg, $ctx); }
                public function critical($msg, $ctx = []) { Core\Logger\Logger::critical($msg, $ctx); }
            };
        }
        
        Core\Logger\Logger::log($level, $message, $context);
    }
}

if (!function_exists('cache')) {
    /**
     * Cache helper
     */
    function cache(?string $key = null, $value = null, $ttl = null) {
        if ($key === null) {
            return Core\Cache\CacheManager::getInstance();
        }
        
        if ($value === null) {
            return Core\Cache\CacheManager::get($key);
        }
        
        Core\Cache\CacheManager::put($key, $value, $ttl ?? 3600);
        return $value;
    }
}

if (!function_exists('event')) {
    /**
     * Fire event
     */
    function event(string $name, ...$payload): void {
        Core\Events\EventDispatcher::fire($name, ...$payload);
    }
}

if (!function_exists('now')) {
    /**
     * Get current datetime
     */
    function now(): string {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('today')) {
    /**
     * Get today's date
     */
    function today(): string {
        return date('Y-m-d');
    }
}

if (!function_exists('str_contains')) {
    /**
     * Check if string contains substring (PHP < 8.0 polyfill)
     */
    function str_contains(string $haystack, string $needle): bool {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('str_starts_with')) {
    /**
     * Check if string starts with substring (PHP < 8.0 polyfill)
     */
    function str_starts_with(string $haystack, string $needle): bool {
        return mb_strpos($haystack, $needle) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    /**
     * Check if string ends with substring (PHP < 8.0 polyfill)
     */
    function str_ends_with(string $haystack, string $needle): bool {
        return $needle !== '' && substr($haystack, -strlen($needle)) === $needle;
    }
}

if (!function_exists('base_path')) {
    /**
     * Get base path
     */
    function base_path(string $path = ''): string {
        return BASE_PATH . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get storage path
     */
    function storage_path(string $path = ''): string {
        return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('public_path')) {
    /**
     * Get public path
     */
    function public_path(string $path = ''): string {
        return base_path('public' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('asset')) {
    /**
     * Generate asset URL with automatic base path detection
     * 
     * @param string $path Path to asset (e.g., '/css/style.css')
     * @return string Full asset URL with base path included
     */
    function asset(string $path): string {
        static $basePath = null;
        
        // Cache base path for performance
        if ($basePath === null) {
            // Check if we're in a web request context
            if (isset($_SERVER['REQUEST_URI'])) {
                // Try to get base path from script name
                $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
                
                // Extract directory from script name
                if (!empty($scriptName)) {
                    // Remove /index.php or other script names to get directory
                    $scriptDir = str_replace('\\', '/', dirname($scriptName));
                    
                    // Normalize root directory
                    if ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '') {
                        $basePath = '';
                    } else {
                        $basePath = $scriptDir;
                    }
                } else {
                    $basePath = '';
                }
            } else {
                // CLI or non-web context - fallback to config
                $basePath = '';
            }
        }
        
        // If base path exists, use it
        if (!empty($basePath)) {
            $path = '/' . ltrim($path, '/');
            return $basePath . $path;
        }
        
        // Fallback to config-based URL generation
        $baseUrl = rtrim(config('app.url'), '/');
        return $baseUrl . '/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    /**
     * Generate URL with automatic base path detection
     * 
     * Supports both subdirectory and root deployments
     * 
     * @param string $path Path to generate URL for (e.g., '/crm/leads')
     * @return string Full URL with base path included
     */
    function url(string $path = ''): string {
        static $basePath = null;
        
        // Cache base path for performance
        if ($basePath === null) {
            // Check if we're in a web request context
            if (isset($_SERVER['REQUEST_URI'])) {
                // Try to get base path from script name
                $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
                
                // Extract directory from script name
                if (!empty($scriptName)) {
                    // Remove /index.php or other script names to get directory
                    $scriptDir = str_replace('\\', '/', dirname($scriptName));
                    
                    // Normalize root directory
                    if ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '') {
                        $basePath = '';
                    } else {
                        $basePath = $scriptDir;
                    }
                } else {
                    $basePath = '';
                }
            } else {
                // CLI or non-web context - fallback to config
                $basePath = '';
            }
        }
        
        // If base path exists, use it
        if (!empty($basePath)) {
            // Normalize path
            $path = '/' . ltrim($path, '/');
            return $basePath . $path;
        }
        
        // Fallback to config-based URL generation
        $baseUrl = rtrim(config('app.url'), '/');
        return $baseUrl . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('abort')) {
    /**
     * Abort with HTTP status
     */
    function abort(int $code, string $message = ''): void {
        http_response_code($code);
        
        if ($message) {
            echo $message;
        } else {
            echo match($code) {
                404 => 'Not Found',
                403 => 'Forbidden',
                401 => 'Unauthorized',
                500 => 'Internal Server Error',
                default => 'Error',
            };
        }
        
        exit(1);
    }
}

if (!function_exists('collect')) {
    /**
     * Create collection (simple array wrapper)
     */
    function collect(array $items = []): array {
        return $items;
    }
}

if (!function_exists('value')) {
    /**
     * Return value or execute closure
     */
    function value($value, ...$args) {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (!function_exists('tap')) {
    /**
     * Tap into value
     */
    function tap($value, callable $callback) {
        $callback($value);
        return $value;
    }
}

if (!function_exists('retry')) {
    /**
     * Retry operation
     */
    function retry(int $times, callable $callback, int $sleep = 0) {
        $attempts = 0;
        
        beginning:
        $attempts++;
        
        try {
            return $callback($attempts);
        } catch (\Exception $e) {
            if ($attempts >= $times) {
                throw $e;
            }
            
            if ($sleep) {
                usleep($sleep * 1000);
            }
            
            goto beginning;
        }
    }
}

if (!function_exists('validate')) {
    /**
     * Validate data
     */
    function validate(array $data, array $rules, array $messages = []): array {
        return Core\Validation\Validator::make($data, $rules, $messages)->validate();
    }
}

if (!function_exists('auth')) {
    /**
     * Get auth instance or authenticated user
     */
    function auth() {
        return new class {
            public function user() { return Core\Auth\Auth::user(); }
            public function id() { return Core\Auth\Auth::id(); }
            public function check() { return Core\Auth\Auth::check(); }
            public function guest() { return Core\Auth\Auth::guest(); }
            public function login($user, $remember = false) { Core\Auth\Auth::login($user, $remember); }
            public function logout() { Core\Auth\Auth::logout(); }
            public function attempt($credentials, $remember = false) { return Core\Auth\Auth::attempt($credentials, $remember); }
        };
    }
}

if (!function_exists('class_basename')) {
    /**
     * Get the class basename of the given object / class
     */
    function class_basename($class): string {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}
