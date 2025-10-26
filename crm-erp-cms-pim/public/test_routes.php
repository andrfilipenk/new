<?php
/**
 * Route Diagnostics
 */

require_once __DIR__ . '/../core/bootstrap.php';
$app = $app();

// Load routes
require_once __DIR__ . '/../app/routes.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Route Diagnostics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h1>Route Diagnostics</h1>
        
        <div class="card mb-3">
            <div class="card-header">Configuration</div>
            <div class="card-body">
                <p><strong>APP_URL:</strong> <?php echo Core\Config\Config::get('app.url'); ?></p>
                <p><strong>Current URL:</strong> <?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?></p>
                <p><strong>Document Root:</strong> <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
                <p><strong>Script Filename:</strong> <?php echo $_SERVER['SCRIPT_FILENAME']; ?></p>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Registered Routes</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>URI</th>
                                <th>Test Link</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $routes = Core\Router::getRoutes();
                            $testRoutes = [
                                '/dashboard',
                                '/crm/leads',
                                '/crm/contacts',
                                '/crm/accounts',
                                '/crm/opportunities',
                                '/crm/activities',
                                '/erp/inventory',
                                '/erp/sales',
                                '/erp/purchases',
                                '/erp/finance',
                                '/cms/pages',
                                '/cms/posts',
                                '/cms/media',
                                '/pim/products',
                                '/pim/categories',
                                '/admin/users',
                                '/admin/roles',
                                '/admin/settings',
                            ];
                            
                            foreach ($routes as $method => $methodRoutes) {
                                foreach ($methodRoutes as $uri => $route) {
                                    if (in_array($uri, $testRoutes)) {
                                        $fullUrl = Core\Config\Config::get('app.url') . $uri;
                                        echo "<tr>";
                                        echo "<td><span class='badge bg-primary'>$method</span></td>";
                                        echo "<td>$uri</td>";
                                        echo "<td><a href='$fullUrl' target='_blank'>Test →</a></td>";
                                        echo "</tr>";
                                    }
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Apache mod_rewrite Status</div>
            <div class="card-body">
                <?php
                if (function_exists('apache_get_modules')) {
                    $modules = apache_get_modules();
                    if (in_array('mod_rewrite', $modules)) {
                        echo '<p class="text-success">✓ mod_rewrite is ENABLED</p>';
                    } else {
                        echo '<p class="text-danger">✗ mod_rewrite is DISABLED</p>';
                        echo '<p>Enable it in XAMPP: Apache config → httpd.conf → Uncomment "LoadModule rewrite_module modules/mod_rewrite.so"</p>';
                    }
                } else {
                    echo '<p class="text-warning">Cannot detect (not running as Apache module)</p>';
                }
                ?>
            </div>
        </div>

        <div class="alert alert-info mt-3">
            <strong>Virtual Host Setup:</strong> Make sure you're accessing via <code>http://crm.local/</code> and not <code>localhost/...</code>
        </div>
    </div>
</body>
</html>
