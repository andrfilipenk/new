<?php
// app/_Core/Mvc/App.php
namespace Core\Mvc;

use Core\Mvc\AbstractModule;
use Core\Di\Injectable;
use Core\Di\Interface\Container as ContainerInterface;
use Core\Events\EventAware;
use Core\Http\Request;
use Core\Http\Response;
use Exception;

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

    public function run()
    {
        $di = $this->getDI();
        $this->fireEvent('app.beforeInitModule', $this);
        $modules = $this->initModules();
        /** @var Request $requ est */
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
                break;
            }
        }
        if (!$route) {
            $exception = new Exception('Route not found');
            $this->fireEvent('app.noRouteFound', $exception);
            $response = $this->createErrorResponse($exception);
            $response->send();
            return;
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

    private function createErrorResponse(Exception $e): Response
    {
        $isDebug  = $this->config['app']['debug'] ?? false;
        $response = $this->di->get('response');
        $response->setStatusCode(500);
        if ($isDebug) {
            $message = sprintf(
                "Error: %s\nFile: %s\nLine: %d\n\nStack Trace:\n%s",
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );
            $response->setContent($message);
            $response->setHeaders(['Content-Type' => 'text/plain']);
            return $response;
        }
        $response->setContent('An unexpected error occurred.');
        return $response;
    }
}