<?php
// Quick route test - access via http://crm.local/test_dashboard_route.php
require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../app/routes.php';

$request = Core\Request::capture();

echo "<!DOCTYPE html><html><head><title>Route Test</title></head><body><pre>\n";
echo "=== Request Information ===\n";
echo "Method: " . $request->method() . "\n";
echo "URI: " . $request->uri() . "\n";
echo "Path: " . $request->path() . "\n";
echo "Base Path: '" . $request->basePath() . "'\n\n";

echo "=== Registered GET Routes ===\n";
$routes = Core\Router::getRoutes();
if (isset($routes['GET'])) {
    foreach ($routes['GET'] as $pattern => $route) {
        echo "  {$pattern}\n";
    }
}

echo "\n=== Testing /dashboard Match ===\n";
$testPath = '/dashboard';
echo "Looking for: '{$testPath}'\n";

if (isset($routes['GET'][$testPath])) {
    echo "✓ Route EXISTS in registry\n";
} else {
    echo "✗ Route NOT FOUND in registry\n";
}

echo "</pre></body></html>";
