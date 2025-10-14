<?php
// app/bootstrap.php
define('APP_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR);
define('APP_DIR', dirname($_SERVER['SCRIPT_NAME'],2));

// Register autoloader
spl_autoload_register(function ($class) {
    $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    foreach (['_', ''] as $sep) {
        $file = APP_PATH . $sep . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    return false;
});

// Load configuration
$config = require 'config.php';
$di = new \Core\Di\Container();
$di->set('config', fn() => $config);

$di->set('eventsManager', fn() => new \Core\Events\Manager);
$di->set('router', fn() => new \Core\Mvc\Router);
$di->set('dispatcher', fn() => new \Core\Mvc\Dispatcher);
$di->set('request', fn() => \Core\Http\Request::capture());
$di->set('response', fn() => \Core\Http\Response::create());

$di->set('db', fn() => new \Core\Database\Database);
$di->set('url', '\Core\Utils\Url');

$di->set('migrationRepository', fn() => new \Core\Database\MigrationRepository);
$di->set('migrator', fn() => new \Core\Database\Migrator);

$di->set('logger', fn() => new \Core\Logging\Logger());
$di->set('exceptionHandler', fn() => new \Core\Exception\ExceptionHandler());


$di->register('\Core\Provider\CookieServiceProvider');
$di->register('\Core\Provider\SessionServiceProvider');
$di->register('\Main\Provider\ViewServiceProvider');
