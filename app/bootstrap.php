<?php
// app/bootstrap.php
define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('APP_PATH', BASE_PATH . 'app' . DIRECTORY_SEPARATOR);
define('APP_DIR', dirname($_SERVER['SCRIPT_NAME'],2));

// Register autoloader
spl_autoload_register(function ($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = APP_PATH . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    return false;
});


// Load configuration
$config = require APP_PATH . 'config.php';
$di = new \Core\Di\Container();

// --- Register Core Services ---
$di->set('config', fn() => $config);

$di->register(new \Module\Provider\SessionServiceProvider());
$di->register(new \Module\Provider\CookieServiceProvider());
$di->register(new \Module\Provider\ViewServiceProvider());
// Register session service
$di->set('session', \Core\Session\Session::class);

// Register cookie service
$di->set('cookie', \Core\Cookie\Cookie::class);

$di->set('db', fn() => new \Core\Database\Database());
$di->set('router', function() use ($config) {
    $router = new \Core\Mvc\Router();
    if (isset($config['modules'])) {
        foreach ($config['modules'] as $module) {
            if (isset($module['routes'])) {
                foreach ($module['routes'] as $pattern => $routeConfig) {
                    $router->add($pattern, $routeConfig);
                }
            }
        }
    }
    return $router;
});


$di->set('dispatcher', fn() => new \Core\Mvc\Dispatcher());
$di->set('eventsManager', fn() => new \Core\Events\Manager());

$di->set(\Core\Database\MigrationRepository::class, fn() => new \Core\Database\MigrationRepository());
$di->set(\Core\Database\Migrator::class, fn() => new \Core\Database\Migrator());

// Create application instance
$app = new \Core\Mvc\Application($di);

return $app;