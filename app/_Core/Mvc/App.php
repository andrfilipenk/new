<?php
// app/_Core/Mvc/App.php
namespace Core\Mvc;

use Core\Mvc\AbstractModule;
use Core\Di\Injectable;
use Core\Events\EventAware;
use Core\Exception\NotFoundException;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Optimized App class with reduced DI lookups and better error handling
 */
class App
{
    use Injectable, EventAware;

    private $dispatcher;

    /**
     * Initialize all Modules
     * 
     * @return \Core\Mvc\AbstractModule[]
     */
    public function initModules(): array
    {
        $modules = [];
        $config  = $this->getDI()->get('config');
        foreach ($config['app']['module'] as $module) {
            $moduleConfig   = [];
            $moduleNs       = $module . '\\';
            $moduleClass    = $moduleNs . 'Module';
            $configFile     = APP_PATH . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'config.php';
            if (!class_exists($moduleClass)) {
                continue;
            }
            if (file_exists($configFile)) {
                $moduleConfig = require $configFile;
            }
            /** @var AbstractModule $moduleInstance */
            $moduleInstance = $this->getDI()->get($moduleClass);
            $moduleInstance->initialize($module, $moduleConfig);
            $modules[] = $moduleInstance;
        }
        return $modules;
    }

    /**
     * Run the application
     */
    public function run()
    {
        $di = $this->getDI();
        $this->fireEvent('app.beforeInitModule', $this);
        $modules = $this->initModules();
        /** @var Request $request */
        $request    = $di->get('request');
        /** @var Response $response */
        $response   = $di->get('response');
        /** @var \Core\Mvc\Router $router */
        $router     = $di->get('\Core\Mvc\Router');
        /** @var \Core\Mvc\Dispatcher $dispatcher */
        $dispatcher = $di->get('\Core\Mvc\Dispatcher');

        $route = false;
        foreach ($modules as $module) {
            $route = $router->matchRouteGroup(
            $request->uri(), 
            $request->method(), 
            $module->getRoutes());
            if ($route) {
                $module->boot($di, $route['module'], $route['controller'], $route['action']);
                break;
            }
        }
        if (!$route) {
            throw new NotFoundException('Route not found', 'Notfound', [
                'uri' => $request->uri(),
                'method' => $request->method(),
            ]);
        }
        $dispatcher->prepare($route);
        $result = $dispatcher->dispatch();
        if (is_array($result)) {
            $response->json($result);
        }
        if (is_string($result)) {
            $response->setContent($result);
        }
        if (is_object($result)) {
            if ($result instanceof Response) {
                $response = $result;
            }
        }
        $this->fireEvent('app.beforeSendResponse', $this);
        $response->send();
        return;
    }
}