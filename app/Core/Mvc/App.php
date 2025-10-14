<?php
namespace Core\Mvc;

use Core\Di\Injectable;
use Core\Exception\NotFoundException;
use Core\Http\Response;

class App
{
    use Injectable;

    protected $middlewareManager;

    public function __construct()
    {
        $this->getDI()->set('app', $this);
        $this->getDI()->set('middlewareManager', '\Core\Mvc\Middleware\MiddlewareManager');
        $this->middlewareManager = $this->getDI()->get('middlewareManager');
        $this->middlewareManager->setDI($this->getDI());
    }

    public function getEventsManager()
    {
        return $this->getDI()->get('eventsManager');
    }

    public function addMiddleware(string $middleware, array $config = []): self
    {
        $this->middlewareManager->add($middleware, $config);
        return $this;
    }

    public function initModules(): array
    {
        $modules = [];
        $config = $this->getDI()->get('config');
        foreach ($config['app']['module'] ?? [] as $module) {
            $moduleConfig = [];
            $moduleClass = "\\{$module}\\Module";
            $configFile = APP_PATH . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . 'config.php';
            if (!class_exists($moduleClass)) {
                continue;
            }
            if (file_exists($configFile)) {
                $moduleConfig = require $configFile;
            }
            /** @var AbstractModule $moduleInstance */
            $moduleInstance = $this->getDI()->get($moduleClass);
            $moduleInstance->setDI($this->getDI());
            $moduleInstance->initialize($module, $moduleConfig);
            $modules[] = $moduleInstance;
        }
        return $modules;
    }

    public function run(): void
    {
        $di = $this->getDI();
        $events = $this->getEventsManager();
        $events->trigger('app.beforeInitModule', $this);

        $modules    = $this->initModules();
        $request    = $di->get('request');
        $response   = $di->get('response');
        $router     = $di->get('router');
        $dispatcher = $di->get('dispatcher');

        $route = false;
        foreach ($modules as $module) {
            $route = $router->matchRouteGroup($request->uri(), $request->method(), $module->getRoutes());
            if ($route) {
                $module->boot($di, $route['module'], $route['controller'], $route['action']);
                break;
            }
        }

        if (!$route) {
            $route = $router->getErrorRoute();
            throw new NotFoundException('Route not found', 'Notfound', [
                'uri' => $request->uri(),
                'method' => $request->method(),
            ]);
        }

        if (!$this->middlewareManager->handle($route['module'], $route['controller'], $route['action'])) {
            $events->trigger('app:middlewareFailed', [$this, $route['module'], $route['controller'], $route['action']]);
            return;
        }

        $events->trigger('app:beforeDispatch', [$this, $dispatcher]);
        $dispatcher->prepare($route);
        $result = $dispatcher->dispatch();

        if (is_array($result)) {
            $response->json($result);
        } elseif (is_string($result)) {
            $response->setContent($result);
        } elseif ($result instanceof Response) {
            $response = $result;
        }

        $events->trigger('app.beforeSendResponse', $this);
        $response->send();
    }
}