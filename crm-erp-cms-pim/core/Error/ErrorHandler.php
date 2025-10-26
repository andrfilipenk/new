<?php

namespace Core\Error;

use Core\Logger\Logger;
use Core\Response;
use Core\Config\Config;

/**
 * Error Handler
 * 
 * Global error and exception handler
 */
class ErrorHandler
{
    protected static bool $registered = false;
    
    /**
     * Register error and exception handlers
     */
    public static function register(): void
    {
        if (static::$registered) {
            return;
        }
        
        set_error_handler([static::class, 'handleError']);
        set_exception_handler([static::class, 'handleException']);
        register_shutdown_function([static::class, 'handleShutdown']);
        
        static::$registered = true;
    }
    
    /**
     * Handle PHP errors
     */
    public static function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }
        
        throw new \ErrorException($message, 0, $level, $file, $line);
    }
    
    /**
     * Handle uncaught exceptions
     */
    public static function handleException(\Throwable $e): void
    {
        try {
            // Log the exception
            static::logException($e);
            
            // Send response
            $response = static::renderException($e);
            $response->send();
        } catch (\Throwable $t) {
            // If error handling fails, output simple error
            echo static::renderFallbackError($t);
        }
        
        exit(1);
    }
    
    /**
     * Handle fatal errors
     */
    public static function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            static::handleException(new \ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            ));
        }
    }
    
    /**
     * Log exception
     */
    protected static function logException(\Throwable $e): void
    {
        $message = sprintf(
            "%s: %s in %s:%d\nStack trace:\n%s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        
        Logger::error($message, [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }
    
    /**
     * Render exception response
     */
    protected static function renderException(\Throwable $e): Response
    {
        $debug = Config::get('app.debug', false);
        $code = static::getHttpCode($e);
        
        // Check if request expects JSON
        if (static::expectsJson()) {
            return static::renderJsonError($e, $code, $debug);
        }
        
        return static::renderHtmlError($e, $code, $debug);
    }
    
    /**
     * Render JSON error
     */
    protected static function renderJsonError(\Throwable $e, int $code, bool $debug): Response
    {
        $data = [
            'error' => static::getErrorTitle($code),
            'message' => $e->getMessage(),
        ];
        
        if ($debug) {
            $data['exception'] = get_class($e);
            $data['file'] = $e->getFile();
            $data['line'] = $e->getLine();
            $data['trace'] = collect(explode("\n", $e->getTraceAsString()))->take(10)->toArray();
        }
        
        return Response::json($data, $code);
    }
    
    /**
     * Render HTML error
     */
    protected static function renderHtmlError(\Throwable $e, int $code, bool $debug): Response
    {
        if ($debug) {
            return static::renderDebugPage($e, $code);
        }
        
        return static::renderProductionPage($e, $code);
    }
    
    /**
     * Render debug error page
     */
    protected static function renderDebugPage(\Throwable $e, int $code): Response
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>' . get_class($e) . '</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: monospace; background: #1a1a1a; color: #fff; padding: 20px; }
                .container { max-width: 1200px; margin: 0 auto; }
                h1 { color: #ff6b6b; margin-bottom: 20px; font-size: 24px; }
                .error-box { background: #2d2d2d; padding: 20px; border-left: 4px solid #ff6b6b; margin-bottom: 20px; }
                .error-message { font-size: 18px; margin-bottom: 10px; color: #fff; }
                .error-location { color: #888; font-size: 14px; }
                .error-location strong { color: #4ecdc4; }
                .trace { background: #2d2d2d; padding: 20px; border-left: 4px solid #4ecdc4; }
                .trace h2 { color: #4ecdc4; margin-bottom: 15px; font-size: 18px; }
                .trace-item { padding: 10px; margin-bottom: 5px; background: #1a1a1a; border-radius: 3px; }
                .trace-item:hover { background: #333; }
                .trace-file { color: #4ecdc4; }
                .trace-line { color: #ff6b6b; }
                .trace-function { color: #ffd93d; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>' . htmlspecialchars(get_class($e)) . '</h1>
                
                <div class="error-box">
                    <div class="error-message">' . htmlspecialchars($e->getMessage()) . '</div>
                    <div class="error-location">
                        in <strong>' . htmlspecialchars($e->getFile()) . '</strong> 
                        on line <strong>' . $e->getLine() . '</strong>
                    </div>
                </div>
                
                <div class="trace">
                    <h2>Stack Trace</h2>';
        
        foreach ($e->getTrace() as $index => $trace) {
            $file = $trace['file'] ?? 'unknown';
            $line = $trace['line'] ?? 0;
            $function = $trace['function'] ?? '';
            $class = $trace['class'] ?? '';
            $type = $trace['type'] ?? '';
            
            $html .= '<div class="trace-item">';
            $html .= '<span style="color:#888">#' . $index . '</span> ';
            $html .= '<span class="trace-file">' . htmlspecialchars($file) . '</span>';
            $html .= ':<span class="trace-line">' . $line . '</span> ';
            if ($class) {
                $html .= '<span class="trace-function">' . htmlspecialchars($class . $type . $function) . '()</span>';
            }
            $html .= '</div>';
        }
        
        $html .= '
                </div>
            </div>
        </body>
        </html>';
        
        return Response::html($html, $code);
    }
    
    /**
     * Render production error page
     */
    protected static function renderProductionPage(\Throwable $e, int $code): Response
    {
        $title = static::getErrorTitle($code);
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>' . $title . '</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }
                .error-container {
                    background: white;
                    border-radius: 20px;
                    padding: 60px;
                    text-align: center;
                    max-width: 600px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                }
                h1 { font-size: 120px; color: #667eea; margin-bottom: 20px; }
                h2 { font-size: 32px; color: #333; margin-bottom: 15px; }
                p { color: #666; font-size: 18px; line-height: 1.6; }
                a { color: #667eea; text-decoration: none; font-weight: 600; }
                a:hover { text-decoration: underline; }
            </style>
        </head>
        <body>
            <div class="error-container">
                <h1>' . $code . '</h1>
                <h2>' . $title . '</h2>
                <p>Sorry, something went wrong. Please try again later.</p>
                <p style="margin-top: 20px;"><a href="/">← Back to Home</a></p>
            </div>
        </body>
        </html>';
        
        return Response::html($html, $code);
    }
    
    /**
     * Render fallback error
     */
    protected static function renderFallbackError(\Throwable $e): string
    {
        return sprintf(
            "<h1>Fatal Error</h1><p>%s</p><p>in %s on line %d</p>",
            htmlspecialchars($e->getMessage()),
            htmlspecialchars($e->getFile()),
            $e->getLine()
        );
    }
    
    /**
     * Get HTTP status code from exception
     */
    protected static function getHttpCode(\Throwable $e): int
    {
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }
        
        $code = $e->getCode();
        return ($code >= 400 && $code < 600) ? $code : 500;
    }
    
    /**
     * Get error title
     */
    protected static function getErrorTitle(int $code): string
    {
        $titles = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            419 => 'Page Expired',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
        ];
        
        return $titles[$code] ?? 'Error';
    }
    
    /**
     * Check if request expects JSON
     */
    protected static function expectsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json') || 
               str_contains($_SERVER['REQUEST_URI'] ?? '', '/api/');
    }
}

// Helper function for collect
if (!function_exists('collect')) {
    function collect(array $items) {
        return new class($items) {
            protected array $items;
            public function __construct(array $items) { $this->items = $items; }
            public function take(int $count) { return new static(array_slice($this->items, 0, $count)); }
            public function toArray() { return $this->items; }
        };
    }
}
