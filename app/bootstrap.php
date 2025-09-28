<?php
// app/bootstrap.php
define('APP_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
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
$di->set('eventsManager', fn() => new \Core\Events\Manager);
$di->set('db', fn() => new \Core\Database\Database);
$di->set('url', fn() => new \Core\Utils\Url);
$di->set('request', fn() => \Core\Http\Request::capture());
$di->set('response', fn() => \Core\Http\Response::create());

$di->register(new \Module\Base\Provider\DatabaseSessionServiceProvider);
$di->register(new \Module\Base\Provider\CookieServiceProvider);
$di->register(new \Module\Base\Provider\ViewServiceProvider);
$di->register(new \Module\Base\Provider\RouterServiceProvider);
$di->register(new \Module\Base\Provider\NavigationServiceProvider);
#$di->register(new \Module\Base\Provider\AclServiceProvider);

$di->set('migrationRepository', fn() => new \Core\Database\MigrationRepository);
$di->set('migrator', concrete: fn() => new \Core\Database\Migrator);