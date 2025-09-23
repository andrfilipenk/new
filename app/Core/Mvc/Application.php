<?php
// app/Core/Mvc/Application.php
namespace Core\Mvc;

use Core\Di\Interface\Container as ContainerInterface;
use Core\Http\Request;
use Core\Http\Response;
use Exception;

class Application
{
    protected $di;

    public function __construct(ContainerInterface $di)
    {
        $this->di = $di;
        $this->di->set('application', $this);
    }

    public function handle(Request $request): Response
    {
        /** @var \Core\Events\Manager $eventsManager */
        $eventsManager = $this->getDI()->get('eventsManager');
        /** @var Router $router */
        $router = $this->getDI()->get('router');
        /** @var Dispatcher $dispatcher */
        $dispatcher = $this->getDI()->get('dispatcher');
        $dispatcher->setDI($this->getDI());
        try {
            $eventsManager->trigger('application:beforeHandle', $this);
            $route = $router->match($request->uri(), $request->method());
            if (!$route) {
                $eventsManager->trigger('application:beforeNotFound', $this);
                return Response::error('Page Not Found', Response::HTTP_NOT_FOUND);
            }
            $response = $dispatcher->dispatch($route, $request);
            if (!$response instanceof Response) {
                // If controller doesn't return a Response, wrap its output
                $response = new Response($response);
            }
            $eventsManager->trigger('application:afterHandle', $this);
            return $response;
        } catch (Exception $e) {
            $event = $eventsManager->trigger('application:onException', [$this, $e]);
            // In production, you would log the error
            // error_log($e->getMessage() . PHP_EOL . $e->getTraceAsString());
            // Return a generic error response
            $config = $this->getDI()->get('config');
            if ($config['app']['debug'] ?? false) {
                return Response::error(
                    "Error: " . $e->getMessage() . 
                    "<br><br>File: " . $e->getFile() . 
                    "<br>Line: " . $e->getLine() . 
                    "<br><br>Stack Trace:<br>" . nl2br($e->getTraceAsString()),
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            } else {
                // Generic error in production
                return Response::error('An unexpected error occurred.', Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        }
    }

    public function getDI(): ContainerInterface
    {
        return $this->di;
    }
}