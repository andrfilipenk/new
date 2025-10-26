<?php
/**
 * Route Testing Script
 * 
 * Tests all critical routes to ensure they're working correctly
 */

// Test URLs to verify
$testRoutes = [
    // Core routes
    ['GET', '/dashboard', 'Dashboard'],
    
    // CRM routes
    ['GET', '/crm/leads', 'CRM Leads'],
    ['GET', '/crm/contacts', 'CRM Contacts'],
    ['GET', '/crm/accounts', 'CRM Accounts'],
    ['GET', '/crm/opportunities', 'CRM Opportunities'],
    ['GET', '/crm/activities', 'CRM Activities'],
    
    // ERP routes
    ['GET', '/erp/inventory', 'ERP Inventory'],
    ['GET', '/erp/sales', 'ERP Sales'],
    ['GET', '/erp/purchases', 'ERP Purchases'],
    ['GET', '/erp/finance', 'ERP Finance'],
    
    // CMS routes
    ['GET', '/cms/pages', 'CMS Pages'],
    ['GET', '/cms/posts', 'CMS Posts'],
    ['GET', '/cms/media', 'CMS Media'],
    
    // PIM routes
    ['GET', '/pim/products', 'PIM Products'],
    ['GET', '/pim/categories', 'PIM Categories'],
    
    // Admin routes
    ['GET', '/admin/users', 'Admin Users'],
    ['GET', '/admin/roles', 'Admin Roles'],
    ['GET', '/admin/settings', 'Admin Settings'],
];

$baseUrl = 'http://localhost/crm-erp-cms-pim/public';
$results = [];

echo "\n=== Route Testing Started ===\n\n";

foreach ($testRoutes as $route) {
    [$method, $path, $name] = $route;
    $url = $baseUrl . $path;
    
    echo "Testing: {$name} ({$method} {$path})... ";
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "ERROR: {$error}\n";
        $results[] = ['route' => $name, 'status' => 'error', 'code' => 0, 'message' => $error];
    } else {
        $status = match(true) {
            $httpCode == 200 => 'OK',
            $httpCode == 302 || $httpCode == 301 => 'REDIRECT (needs auth)',
            $httpCode == 404 => 'NOT FOUND',
            $httpCode == 403 => 'FORBIDDEN',
            $httpCode == 500 => 'SERVER ERROR',
            default => "HTTP {$httpCode}"
        };
        
        echo "{$status} [{$httpCode}]\n";
        $results[] = ['route' => $name, 'status' => $status, 'code' => $httpCode];
    }
}

echo "\n=== Summary ===\n\n";

$grouped = [];
foreach ($results as $result) {
    $key = $result['status'];
    if (!isset($grouped[$key])) {
        $grouped[$key] = [];
    }
    $grouped[$key][] = $result['route'];
}

foreach ($grouped as $status => $routes) {
    echo "{$status}: " . count($routes) . " routes\n";
    foreach ($routes as $route) {
        echo "  - {$route}\n";
    }
    echo "\n";
}

echo "Total routes tested: " . count($results) . "\n";
echo "\n=== Testing Complete ===\n";
