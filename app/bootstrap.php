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
$di->set('config', fn() => $config);
$di->register(new \Module\Provider\SessionServiceProvider);
$di->register(new \Module\Provider\CookieServiceProvider);
$di->register(new \Module\Provider\ViewServiceProvider);
$di->register(new \Module\Provider\RouterServiceProvider);
$di->set('url', fn() => new \Core\Utils\Url);
$di->set('request', fn() => new \Core\Http\Request);
$di->set('db', fn() => new \Core\Database\Database);
$di->set('dispatcher', fn() => new \Core\Mvc\Dispatcher);
$di->set('eventsManager', fn() => new \Core\Events\Manager);
$di->set('migrationRepository', fn() => new \Core\Database\MigrationRepository);
$di->set('migrator', fn() => new \Core\Database\Migrator);

$app = new \Core\Mvc\Application($di);
return $app;