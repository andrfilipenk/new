<?php
// app/Core/Mvc/Application.php
namespace Core\Mvc;

use App\Core\Mvc\AbstractModule;
use Core\Di\Injectable;
use Core\Di\Interface\Container as ContainerInterface;
use Core\Events\EventAware;
use Core\Http\Request;
use Core\Http\Response;
use Exception;

/**
 * Optimized Application class with reduced DI lookups and better error handling
 */
class Application
{
    use Injectable, EventAware;
    private $dispatcher;
    private $config;
    /**
     * Holds all registred Modules
     * @var \App\Core\Mvc\AbstractModule[]
     */
    protected $modules = [];



    public function collectModuleRoutes()
    {
        $config  = $this->getDI()->get('config');
        $routes  = $config['app']['routes'];
        $modules = $config['app']['modules'];
        foreach ($modules as $module) {
            /** @var AbstractModule $moduleInstance */
            $moduleNs       = $module . '\\';
            $moduleClass    = $moduleNs . 'Module';
            $configFile     = $moduleNs . 'config.php';
            $moduleInstance = $this->getDI()->get($moduleClass);
            if (file_exists($configFile)) {
                $moduleConfig = require $configFile;
                $moduleRoutes = $moduleConfig['routes'] ?? false;
                if ($moduleRoutes) {
                    $routes = array_merge($routes, $moduleRoutes);
                    unset($moduleConfig['routes']);
                }
                $moduleInstance->setConfig($moduleConfig);
            }
            $moduleInstance->initialize();
            $this->fireEvent('application:afterModuleInit', $moduleInstance);
        }
        return $routes;
    }



    public function run(Request $request, ContainerInterface  $container)
    {
    }

    public function handle(Request $request): Response
    {
        try {
            $this->eventsManager->trigger('application:beforeHandle', $this);
            $base = $this->config['app']['base'] ?? '';
            $uri = $request->uri($base);
            $route = $this->router->match($uri, $request->method());

            if (!$route) {
                $response = $this->di->get('response');
                $response->setStatusCode(404);
                $response->setContent('Route not found');
                
                $event = $this->eventsManager->trigger('application:beforeNotFound', $response);
                $response = $event->getData() ?: $response;
                return $response;
            }

            $dispatcher = $this->dispatcher->prepare($route);

            $event = $this->eventsManager->trigger('application:beforeDispatch', $dispatcher);
            $dispatcher = $event->getData();
            
            $dispatcher->prepare($route);
            $result = $dispatcher->dispatch($route, $request);
            
            $event = $this->eventsManager->trigger('application:afterDispatch', $result);
            $result = $event->getData();

            return $this->createResponse($result);
        } catch (Exception $e) {
            $this->eventsManager->trigger('application:onException', [$this, $e]);
            return $this->createErrorResponse($e);
        }
    }

    private function createResponse($result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }
        $response = $this->di->get('response');
        if (is_array($result)) {
            return $response->json($result);
        }
        return $response->setContent((string)$result);
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