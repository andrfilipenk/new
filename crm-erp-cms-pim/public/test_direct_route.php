<?php
/**
 * Complete Routing Test - Direct Invocation
 * This bypasses the web server to test routing directly
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// Simulate a web request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/login';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['HTTP_HOST'] = 'crm.local';

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../app/routes.php';

echo "<!DOCTYPE html><html><head><title>Direct Route Test</title></head><body><pre>\n";
echo "=== Direct Route Invocation Test ===\n\n";

try {
    $request = Core\Request::capture();
    
    echo "Request Details:\n";
    echo "  Method: " . $request->method() . "\n";
    echo "  URI: " . $request->uri() . "\n";
    echo "  Path: " . $request->path() . "\n";
    echo "  Base Path: '" . $request->basePath() . "'\n\n";
    
    echo "Dispatching request...\n\n";
    
    $response = Core\Router::dispatch($request);
    
    echo "Response received:\n";
    echo "  Status Code: " . $response->getStatusCode() . "\n";
    echo "  Content Type: " . ($response->getHeader('Content-Type') ?? 'not set') . "\n";
    
    $content = $response->getContent();
    if (is_string($content)) {
        $contentPreview = substr($content, 0, 200);
        echo "  Content Preview: " . htmlspecialchars($contentPreview) . "...\n";
    } else {
        echo "  Content: " . print_r($content, true) . "\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre></body></html>";
