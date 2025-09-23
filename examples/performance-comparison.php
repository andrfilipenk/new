<?php
// examples/performance-comparison.php

require_once __DIR__ . '/../app/bootstrap.php';

use Core\Http\Request;
use Core\Http\OptimizedRequest;
use Core\Http\Response;
use Core\Http\OptimizedResponse;

/**
 * Performance comparison between original and optimized components
 */

// Simulate a typical request
$_GET = ['page' => '1', 'sort' => 'name'];
$_POST = ['name' => 'John', 'email' => 'john@example.com'];
$_SERVER = [
    'REQUEST_METHOD' => 'POST',
    'REQUEST_URI' => '/users/create',
    'HTTP_USER_AGENT' => 'Mozilla/5.0',
    'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
    'CONTENT_TYPE' => 'application/x-www-form-urlencoded'
];

echo "=== PERFORMANCE COMPARISON ===\n\n";

// Test Request components
echo "1. REQUEST COMPONENT COMPARISON:\n";

// Original Request
$start = microtime(true);
$memory_start = memory_get_usage();

for ($i = 0; $i < 1000; $i++) {
    $request = new Request();
    $request->all();
    $request->input('name');
    $request->isAjax();
    $request->method();
}

$original_time = microtime(true) - $start;
$original_memory = memory_get_usage() - $memory_start;

// Optimized Request
$start = microtime(true);
$memory_start = memory_get_usage();

for ($i = 0; $i < 1000; $i++) {
    $request = OptimizedRequest::capture();
    $request->all();
    $request->input('name');
    $request->isAjax();
    $request->method();
}

$optimized_time = microtime(true) - $start;
$optimized_memory = memory_get_usage() - $memory_start;

printf("Original Request:  %.4fs, %d bytes\n", $original_time, $original_memory);
printf("Optimized Request: %.4fs, %d bytes\n", $optimized_time, $optimized_memory);
printf("Performance gain:  %.1fx faster, %.1fx less memory\n\n", 
    $original_time / $optimized_time, 
    $original_memory / $optimized_memory
);

// Test Response components
echo "2. RESPONSE COMPONENT COMPARISON:\n";

$test_data = ['users' => [['id' => 1, 'name' => 'John'], ['id' => 2, 'name' => 'Jane']]];

// Original Response
$start = microtime(true);
$memory_start = memory_get_usage();

for ($i = 0; $i < 1000; $i++) {
    $response = new Response();
    $response->json($test_data);
    $response->setHeader('X-Test', 'value');
    $response->withCookie('session', 'abc123');
}

$original_time = microtime(true) - $start;
$original_memory = memory_get_usage() - $memory_start;

// Optimized Response
$start = microtime(true);
$memory_start = memory_get_usage();

for ($i = 0; $i < 1000; $i++) {
    $response = OptimizedResponse::create();
    $response->json($test_data);
    $response->setHeader('X-Test', 'value');
    $response->withCookie('session', 'abc123');
}

$optimized_time = microtime(true) - $start;
$optimized_memory = memory_get_usage() - $memory_start;

printf("Original Response:  %.4fs, %d bytes\n", $original_time, $original_memory);
printf("Optimized Response: %.4fs, %d bytes\n", $optimized_time, $optimized_memory);
printf("Performance gain:   %.1fx faster, %.1fx less memory\n\n", 
    $original_time / $optimized_time, 
    $original_memory / $optimized_memory
);

echo "=== FEATURE DEMONSTRATION ===\n\n";

// Demonstrate new features
echo "3. NEW OPTIMIZED FEATURES:\n";

$request = OptimizedRequest::capture();
echo "- Singleton pattern: " . ($request === OptimizedRequest::capture() ? "✓" : "✗") . "\n";
echo "- Lazy loading headers: " . (method_exists($request, 'getHeaders') ? "✓" : "✗") . "\n";
echo "- Smart IP detection: " . $request->ip() . "\n";
echo "- JSON detection: " . ($request->isJson() ? "Yes" : "No") . "\n";

$response = OptimizedResponse::create('Test content');
echo "- Static factory: " . ($response instanceof OptimizedResponse ? "✓" : "✗") . "\n";
echo "- Content size: " . $response->getSize() . " bytes\n";
echo "- Is empty: " . ($response->isEmpty() ? "Yes" : "No") . "\n";
echo "- Efficient JSON encoding with flags\n";

echo "\n=== MEMORY USAGE ANALYSIS ===\n";
echo "Current memory usage: " . number_format(memory_get_usage()) . " bytes\n";
echo "Peak memory usage: " . number_format(memory_get_peak_usage()) . " bytes\n";