<?php
// examples/optimized-usage-example.php

require_once __DIR__ . '/../app/bootstrap.php';

use Core\Http\OptimizedRequest;
use Core\Http\OptimizedResponse;
use Core\Mvc\OptimizedApplication;

/**
 * Example of how to use the optimized components
 */

echo "=== OPTIMIZED COMPONENTS USAGE EXAMPLE ===\n\n";

// 1. Using OptimizedRequest
echo "1. OPTIMIZED REQUEST USAGE:\n";

// Simulate request data
$_GET = ['page' => '1', 'filter' => 'active'];
$_POST = ['name' => 'John Doe', 'email' => 'john@example.com'];
$_SERVER = [
    'REQUEST_METHOD' => 'POST',
    'REQUEST_URI' => '/api/users/create',
    'HTTP_CONTENT_TYPE' => 'application/json',
    'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
    'HTTP_USER_AGENT' => 'Mozilla/5.0 (Test Browser)',
    'REMOTE_ADDR' => '192.168.1.100'
];

// Create optimized request (singleton pattern)
$request = OptimizedRequest::capture();

echo "Method: " . $request->method() . "\n";
echo "URI: " . $request->uri() . "\n";
echo "Is AJAX: " . ($request->isAjax() ? 'Yes' : 'No') . "\n";
echo "Is JSON: " . ($request->isJson() ? 'Yes' : 'No') . "\n";
echo "User Agent: " . $request->userAgent() . "\n";
echo "IP Address: " . $request->ip() . "\n";
echo "Get param 'page': " . $request->get('page') . "\n";
echo "Post param 'name': " . $request->post('name') . "\n";
echo "Input 'email': " . $request->input('email') . "\n";
echo "Has 'filter': " . ($request->has('filter') ? 'Yes' : 'No') . "\n";

echo "\nAll inputs: ";
print_r($request->all());

echo "\n2. OPTIMIZED RESPONSE USAGE:\n";

// Create different types of responses
$response1 = OptimizedResponse::create('Hello World');
echo "Simple response: " . $response1->getContent() . "\n";
echo "Size: " . $response1->getSize() . " bytes\n";

// JSON response
$data = ['status' => 'success', 'data' => ['id' => 1, 'name' => 'John']];
$response2 = OptimizedResponse::create()->json($data);
echo "JSON response: " . $response2->getContent() . "\n";

// Response with cookies
$response3 = OptimizedResponse::create('Welcome!')
    ->setHeader('X-Custom', 'MyApp')
    ->withCookie('session_id', 'abc123', time() + 3600)
    ->setStatusCode(201);

echo "Response with cookie headers:\n";
foreach ($response3->getHeaders() as $name => $value) {
    if (is_array($value)) {
        foreach ($value as $v) {
            echo "  $name: $v\n";
        }
    } else {
        echo "  $name: $value\n";
    }
}

// Redirect response
$response4 = OptimizedResponse::create()->redirect('/dashboard', 302);
echo "Redirect location: " . $response4->getHeader('Location') . "\n";
echo "Redirect status: " . $response4->getStatusCode() . "\n";

// Error response
$response5 = OptimizedResponse::create()->error('Not found', 404);
echo "Error response: " . $response5->getContent() . " (Status: " . $response5->getStatusCode() . ")\n";

echo "\n3. INTEGRATION WITH DI CONTAINER:\n";

// Show how to register optimized components in DI
$di = new \Core\Di\Container();

// Register optimized components
$di->set('request', function() {
    return OptimizedRequest::capture();
});

$di->set('response', function() {
    return new OptimizedResponse();
});

// Usage in controllers
echo "Request from DI: " . get_class($di->get('request')) . "\n";
echo "Response from DI: " . get_class($di->get('response')) . "\n";

echo "\n4. BENCHMARKING LAZY LOADING:\n";

// Demonstrate lazy loading benefits
$start = microtime(true);
$memory_start = memory_get_usage();

$request = OptimizedRequest::capture();
// These operations don't trigger header parsing yet
$request->get('page');
$request->post('name');
$request->method();

$time_without_headers = microtime(true) - $start;
$memory_without_headers = memory_get_usage() - $memory_start;

// Now trigger header parsing
$start = microtime(true);
$memory_start = memory_get_usage();

$request->header('User-Agent'); // This triggers header parsing

$time_with_headers = microtime(true) - $start;
$memory_with_headers = memory_get_usage() - $memory_start;

printf("Operations without header parsing: %.6fs, %d bytes\n", $time_without_headers, $memory_without_headers);
printf("Header parsing triggered: %.6fs, %d bytes\n", $time_with_headers, $memory_with_headers);

echo "\n=== OPTIMIZATION BENEFITS ===\n";
echo "✓ Lazy loading reduces initial memory footprint\n";
echo "✓ Singleton pattern prevents duplicate instances\n";
echo "✓ Efficient array operations with caching\n";
echo "✓ Smart content type detection\n";
echo "✓ Reduced DI container lookups\n";
echo "✓ Better error handling with less string manipulation\n";
echo "✓ Optimized JSON encoding with flags\n";
echo "✓ Streamlined cookie header generation\n";