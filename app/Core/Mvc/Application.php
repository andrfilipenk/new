<?php
// app/Core/Mvc/Application.php
namespace Core\Mvc;

use Core\Di\Interface\Container as ContainerInterface;
use Core\Http\Request;
use Core\Http\Response;
use Exception;

/**
 * Optimized Application class with reduced DI lookups and better error handling
 */
class Application
{
    private ContainerInterface $di;
    private $eventsManager;
    private $router;
    private $dispatcher;
    private $config;

    public function __construct(ContainerInterface $di)
    {
        $this->di = $di;
        $this->di->set('application', $this);
        
        // Cache frequently used services
        $this->eventsManager = $this->di->get('eventsManager');
        $this->router = $this->di->get('router');
        $this->dispatcher = $this->di->get('dispatcher');
        $this->dispatcher->setDI($this->di);
        $this->config = $this->di->get('config');
    }

    public function handle(Request $request): Response
    {
        try {
            $this->eventsManager->trigger('application:beforeHandle', $this);
            $base = $this->config['app']['base'] ?? '';
            $route = $this->router->match($request->uri($base), $request->method());
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

    public function getDI(): ContainerInterface
    {
        return $this->di;
    }
}