<?php
/**
 * Route Diagnostic Tool
 * 
 * This script helps diagnose routing issues by showing:
 * - All registered routes
 * - Current request information
 * - Path normalization details
 */

// Bootstrap the application
require_once __DIR__ . '/../core/bootstrap.php';

// Load routes
require_once __DIR__ . '/../app/routes.php';

// Get request information
$request = Core\Request::capture();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Route Diagnostics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container my-5">
        <h1 class="mb-4">Route Diagnostics</h1>
        
        <!-- Current Request Information -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Current Request Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th width="200">REQUEST_URI:</th>
                        <td><code><?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A'); ?></code></td>
                    </tr>
                    <tr>
                        <th>SCRIPT_NAME:</th>
                        <td><code><?php echo htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? 'N/A'); ?></code></td>
                    </tr>
                    <tr>
                        <th>HTTP Method:</th>
                        <td><code><?php echo htmlspecialchars($request->method()); ?></code></td>
                    </tr>
                    <tr>
                        <th>Detected Base Path:</th>
                        <td><code><?php echo htmlspecialchars($request->basePath()); ?></code></td>
                    </tr>
                    <tr>
                        <th>Normalized Path:</th>
                        <td><code><?php echo htmlspecialchars($request->path()); ?></code></td>
                    </tr>
                    <tr>
                        <th>Full URI:</th>
                        <td><code><?php echo htmlspecialchars($request->uri()); ?></code></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Registered Routes -->
        <?php
        $routes = Core\Router::getRoutes();
        foreach ($routes as $method => $methodRoutes): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><?php echo $method; ?> Routes (<?php echo count($methodRoutes); ?> total)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($methodRoutes)): ?>
                        <p class="text-muted">No routes registered for this method</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Route Pattern</th>
                                        <th>Matches Current?</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($methodRoutes as $pattern => $route): ?>
                                        <?php 
                                        $matches = ($pattern === $request->path()) || preg_match('#^' . preg_replace('/\{(\w+)(\?)?\}/', '([^/]+)?', $pattern) . '$#', $request->path());
                                        ?>
                                        <tr class="<?php echo $matches ? 'table-success' : ''; ?>">
                                            <td><code><?php echo htmlspecialchars($pattern); ?></code></td>
                                            <td>
                                                <?php if ($matches): ?>
                                                    <span class="badge bg-success">YES ✓</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- URL Helper Test -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">URL Helper Test</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Input</th>
                            <th>Generated URL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>url('/dashboard')</code></td>
                            <td><code><?php echo htmlspecialchars(url('/dashboard')); ?></code></td>
                        </tr>
                        <tr>
                            <td><code>url('/crm/leads')</code></td>
                            <td><code><?php echo htmlspecialchars(url('/crm/leads')); ?></code></td>
                        </tr>
                        <tr>
                            <td><code>asset('/css/style.css')</code></td>
                            <td><code><?php echo htmlspecialchars(asset('/css/style.css')); ?></code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Test Links -->
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h5 class="mb-0">Test Navigation Links</h5>
            </div>
            <div class="card-body">
                <p>Click these links to test routing:</p>
                <div class="d-grid gap-2">
                    <a href="<?php echo url('/dashboard'); ?>" class="btn btn-outline-primary">Dashboard</a>
                    <a href="<?php echo url('/crm/leads'); ?>" class="btn btn-outline-primary">CRM Leads</a>
                    <a href="<?php echo url('/crm/contacts'); ?>" class="btn btn-outline-primary">CRM Contacts</a>
                    <a href="<?php echo url('/erp/inventory'); ?>" class="btn btn-outline-success">ERP Inventory</a>
                    <a href="<?php echo url('/cms/pages'); ?>" class="btn btn-outline-info">CMS Pages</a>
                    <a href="<?php echo url('/pim/products'); ?>" class="btn btn-outline-warning">PIM Products</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
