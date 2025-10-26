<?php

/**
 * Front Controller
 * 
 * All requests are routed through this file
 */

// Bootstrap the framework
$app = require_once __DIR__ . '/../core/bootstrap.php';

// Initialize application
$application = $app();

// Capture the request
$request = Core\Request::capture();

// Example: Simple routing (will be replaced by Router)
$path = $request->path();

// Load routes
require_once __DIR__ . '/../app/routes.php';

// Handle request
try {
    $response = Core\Router::dispatch($request);
} catch (\Exception $e) {
    // Error handling
    if (Core\Config\Config::get('app.debug', false)) {
        $response = Core\Response::html('
            <!DOCTYPE html>
            <html>
            <head>
                <title>Error</title>
                <style>
                    body { font-family: monospace; padding: 20px; background: #f8d7da; }
                    h1 { color: #721c24; }
                    pre { background: white; padding: 20px; border: 1px solid #f5c6cb; }
                </style>
            </head>
            <body>
                <h1>Application Error</h1>
                <p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
                <p><strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>
                <h2>Stack Trace:</h2>
                <pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>
            </body>
            </html>
        ', 500);
    } else {
        $response = Core\Response::html('
            <!DOCTYPE html>
            <html>
            <head>
                <title>Error</title>
                <style>
                    body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                    h1 { color: #e74c3c; }
                </style>
            </head>
            <body>
                <h1>500 - Internal Server Error</h1>
                <p>Something went wrong. Please try again later.</p>
            </body>
            </html>
        ', 500);
    }
}

// Age flash data
Core\Session\SessionManager::ageFlashData();

// Send response
$response->send();
