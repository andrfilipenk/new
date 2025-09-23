<?php
// examples/standalone-optimization-demo.php

// Autoload simulation for demo
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Include the optimized classes
require_once __DIR__ . '/../app/Core/Http/OptimizedRequest.php';
require_once __DIR__ . '/../app/Core/Http/OptimizedResponse.php';
require_once __DIR__ . '/../app/Core/Http/UploadedFile.php';

use Core\Http\OptimizedRequest;
use Core\Http\OptimizedResponse;

/**
 * Standalone demonstration of optimized HTTP components
 */

echo "=== OPTIMIZED HTTP COMPONENTS DEMO ===\n\n";

// Simulate request environment
$_GET = ['page' => '1', 'sort' => 'name', 'filter' => 'active'];
$_POST = ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => '30'];
$_SERVER = [
    'REQUEST_METHOD' => 'POST',
    'REQUEST_URI' => '/api/users/create?debug=1',
    'HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded',
    'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
    'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'HTTP_ACCEPT' => 'application/json, text/plain, */*',
    'HTTP_X_FORWARDED_FOR' => '203.0.113.1',
    'REMOTE_ADDR' => '192.168.1.100',
    'HTTPS' => 'on'
];

echo "1. OPTIMIZED REQUEST FEATURES:\n";
echo str_repeat("-", 40) . "\n";

// Test OptimizedRequest
$request = OptimizedRequest::capture();

// Basic information
echo "Method: " . $request->method() . "\n";
echo "URI: " . $request->uri() . "\n";
echo "IP Address: " . $request->ip() . " (supports X-Forwarded-For)\n";
echo "Is AJAX: " . ($request->isAjax() ? 'Yes' : 'No') . "\n";
echo "Is Secure: " . ($request->isSecure() ? 'Yes' : 'No') . "\n";
echo "Is JSON: " . ($request->isJson() ? 'Yes' : 'No') . "\n";
echo "User Agent: " . substr($request->userAgent(), 0, 50) . "...\n";

// Input methods
echo "\nInput Methods:\n";
echo "GET 'page': " . $request->get('page') . "\n";
echo "POST 'name': " . $request->post('name') . "\n";
echo "INPUT 'email': " . $request->input('email') . "\n";
echo "Has 'filter': " . ($request->has('filter') ? 'Yes' : 'No') . "\n";
echo "Has 'missing': " . ($request->has('missing') ? 'Yes' : 'No') . "\n";

// Singleton pattern test
$request2 = OptimizedRequest::capture();
echo "\nSingleton Pattern: " . ($request === $request2 ? 'Working ✓' : 'Failed ✗') . "\n";

echo "\n2. OPTIMIZED RESPONSE FEATURES:\n";
echo str_repeat("-", 40) . "\n";

// Simple response
$response1 = OptimizedResponse::create('Hello, World!');
echo "Simple Response: " . $response1->getContent() . "\n";
echo "Content Size: " . $response1->getSize() . " bytes\n";
echo "Is Empty: " . ($response1->isEmpty() ? 'Yes' : 'No') . "\n";

// JSON response
$data = [
    'status' => 'success',
    'data' => [
        'users' => [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']
        ]
    ],
    'meta' => ['total' => 2, 'page' => 1]
];

$response2 = OptimizedResponse::create()->json($data, 201);
echo "\nJSON Response (first 100 chars): " . substr($response2->getContent(), 0, 100) . "...\n";
echo "Status Code: " . $response2->getStatusCode() . "\n";
echo "Content Type: " . $response2->getHeader('Content-Type') . "\n";

// Response with headers and cookies
$response3 = OptimizedResponse::create('User created successfully')
    ->setStatusCode(201)
    ->setHeader('X-API-Version', '1.0')
    ->setHeader('X-Rate-Limit', '100')
    ->withCookie('session_id', 'abc123def456', time() + 3600, '/', '', true, true)
    ->withCookie('preferences', 'theme=dark', time() + 86400);

echo "\nResponse with Headers and Cookies:\n";
echo "Content: " . $response3->getContent() . "\n";
echo "Status: " . $response3->getStatusCode() . "\n";
echo "Headers:\n";
foreach ($response3->getHeaders() as $name => $value) {
    if (is_array($value)) {
        foreach ($value as $v) {
            echo "  $name: $v\n";
        }
    } else {
        echo "  $name: $value\n";
    }
}

// Error response
$response4 = OptimizedResponse::create()->error('Validation failed', 422);
echo "\nError Response: " . $response4->getContent() . " (Status: " . $response4->getStatusCode() . ")\n";

// Redirect response
$response5 = OptimizedResponse::create()->redirect('/dashboard', 302);
echo "Redirect: " . $response5->getHeader('Location') . " (Status: " . $response5->getStatusCode() . ")\n";

echo "\n3. PERFORMANCE BENEFITS:\n";
echo str_repeat("-", 40) . "\n";

// Demonstrate lazy loading
echo "Lazy Loading Test:\n";
$start = microtime(true);
$memory_start = memory_get_usage();

// Create request but don't access headers yet
$test_request = OptimizedRequest::capture();
$test_request->get('page');
$test_request->post('name');
$test_request->method();

$time1 = microtime(true) - $start;
$memory1 = memory_get_usage() - $memory_start;

// Now trigger header parsing
$start = microtime(true);
$memory_start = memory_get_usage();
$test_request->header('User-Agent');
$time2 = microtime(true) - $start;
$memory2 = memory_get_usage() - $memory_start;

printf("Basic operations (no headers): %.6fs, %d bytes\n", $time1, $memory1);
printf("Header access (triggers parsing): %.6fs, %d bytes\n", $time2, $memory2);

// Memory efficiency test
echo "\nMemory Efficiency:\n";
$responses = [];
$memory_before = memory_get_usage();

for ($i = 0; $i < 100; $i++) {
    $responses[] = OptimizedResponse::create("Response $i");
}

$memory_after = memory_get_usage();
printf("100 response objects: %d bytes (avg: %d bytes per object)\n", 
    $memory_after - $memory_before, 
    ($memory_after - $memory_before) / 100
);

echo "\n4. KEY OPTIMIZATIONS SUMMARY:\n";
echo str_repeat("-", 40) . "\n";
echo "✓ Singleton pattern for Request (prevents duplicate instances)\n";
echo "✓ Lazy loading of headers, files, and JSON (only when needed)\n";
echo "✓ Efficient array operations with caching\n";
echo "✓ Smart IP detection (supports proxy headers)\n";
echo "✓ Optimized JSON encoding with flags\n";
echo "✓ Streamlined cookie header generation\n";
echo "✓ Reduced memory footprint per object\n";
echo "✓ Better type hints and return types\n";
echo "✓ Static factory methods for cleaner API\n";

echo "\n=== DEMO COMPLETED ===\n";