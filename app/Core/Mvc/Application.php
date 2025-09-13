<?php
// app/Core/Application.php
namespace Core\Mvc;

use Core\Database\Database;
use Core\Di\Container;
use Core\Di\ContainerBuilder;
use Core\Events\Manager as EventsManager;

class Application
{
    protected $di;
    protected $eventsManager;
    protected $router;
    protected $dispatcher;
    protected $modules = [];

    public function __construct(array $config = [])
    {
        // Build dependency injection container
        $builder = new ContainerBuilder();
        // Register core services
        $builder->addDefinition('config', $config);
        $builder->addDefinition('eventsManager', EventsManager::class);
        $builder->addDefinition('db', Database::class);
        $builder->addDefinition('router', Router::class);
        $builder->addDefinition('dispatcher', function($di) {
            return new Dispatcher($di);
        });
        $builder->addDefinition('application', $this);
        // Build the container
        $this->di = $builder->build();
        // Get instances
        $this->eventsManager = $this->di->get('eventsManager');
        $this->router = $this->di->get('router');
        $this->dispatcher = $this->di->get('dispatcher');

        // Set default events manager for EventAware components
        //EventsManager::setDefault($this->eventsManager);
    }

    public function registerModules(array $modules): void
    {
        $this->modules = $modules;
        // Register module routes
        foreach ($modules as $module) {
            if (isset($module['routes'])) {
                $this->registerRoutes($module['routes']);
            }
            if (isset($module['services'])) {
                $this->registerServices($module['services']);
            }
        }
    }

    public function registerRoutes(array $routes): void
    {
        foreach ($routes as $pattern => $config) {
            $this->router->add($pattern, $config);
        }
    }

    public function registerServices(array $services): void
    {
        foreach ($services as $name => $service) {
            $this->di->set($name, $service);
        }
    }

    public function handle(string $uri): void
    {
        try {
            // Trigger beforeHandle event
            $this->eventsManager->trigger('application:beforeHandle', $this);
            // Route the request
            $route = $this->router->match($uri);

            var_dump($route);
            if (!$route) {
                // Trigger beforeNotFound event
                $event = $this->eventsManager->trigger('application:beforeNotFound', $this, [
                    'uri' => $uri
                ]);
                if (!$event->isPropagationStopped()) {
                    // Default 404 handling
                    http_response_code(404);
                    echo 'Page not found';
                }
                return;
            }

            // Dispatch the request
            $this->dispatcher->dispatch($route);
            // Trigger afterHandle event
            $this->eventsManager->trigger('application:afterHandle', $this);
        } catch (\Exception $e) {
            // Trigger onException event
            $event = $this->eventsManager->trigger('application:onException', $this, [
                'exception' => $e
            ]);
            if (!$event->isPropagationStopped()) {
                // Default exception handling
                http_response_code(500);
                echo 'An error occurred: ' . $e->getMessage();
                // Log error in production
                if (ini_get('display_errors') !== '1') {
                    error_log($e->getMessage());
                }
            }
        }
    }

    public function getDI(): Container
    {
        return $this->di;
    }

    public function getEventsManager(): EventsManager
    {
        return $this->eventsManager;
    }
}