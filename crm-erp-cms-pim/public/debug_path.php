<?php
/**
 * Simple Path Debug Script
 * Direct output without session conflicts
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "=== PHP Path Debugging ===\n\n";

echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'NOT SET') . "\n";
echo "SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'NOT SET') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'NOT SET') . "\n";

// Simulate the Request class logic
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
if (!empty($scriptName)) {
    $scriptDir = str_replace('\\', '/', dirname($scriptName));
    if ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '') {
        $basePath = '';
    } else {
        $basePath = $scriptDir;
    }
} else {
    $basePath = '';
}

echo "\n=== Calculated Values ===\n\n";
echo "Base Path: '" . $basePath . "'\n";

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?? '/';

echo "Parsed Path (before base removal): '" . $path . "'\n";

if (!empty($basePath) && str_starts_with($path, $basePath)) {
    $path = substr($path, strlen($basePath));
}

if (empty($path) || $path[0] !== '/') {
    $path = '/' . $path;
}

// Normalize double slashes
$path = preg_replace('#/+#', '/', $path);

// Remove trailing slash unless root
if ($path !== '/' && str_ends_with($path, '/')) {
    $path = rtrim($path, '/');
}

echo "Normalized Path (after processing): '" . $path . "'\n";
