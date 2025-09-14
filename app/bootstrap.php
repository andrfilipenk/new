<?php

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
$di->set('view', function() use ($di) {
    $viewConfig = $di->get('config')['view'];
    $view = new \Core\View\View($viewConfig['path']);
    if (isset($viewConfig['layout'])) {
        $view->setLayout($viewConfig['layout']);
    }
    return $view;
});


// Create application instance
$app = new \Core\Mvc\Application($di);

return $app;