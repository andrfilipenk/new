<?php
// app/Core/Dispatcher.php
namespace Core\Mvc;

use Core\Di\Interface\Container as ContainerInterface;
use Core\Di\Injectable;
use Core\Events\EventAware;

class Dispatcher
{
    use Injectable, EventAware;

    protected $handler;
    protected $action;
    protected $params;
    protected $actionName;
    protected $controllerName;

    public function __construct(ContainerInterface $di)
    {
        $this->setDI($di);
    }

    public function dispatch(array $route): void
    {
        // Extract route information
        $handlerClass = $route['controller'];
        $action = $route['action'];
        $params = $route['params'] ?? [];

        // Store action and controller names
        $this->actionName = $action;
        $this->controllerName = $handlerClass;

        // Trigger beforeDispatch event
        $event = $this->fireEvent('core:beforeDispatch', [
            'handler' => $handlerClass,
            'action' => $action,
            'params' => $params
        ]);

        // Check if event stopped propagation
        if ($event->isPropagationStopped()) {
            return;
        }

        // Instantiate the controller
        if (!class_exists($handlerClass)) {
            throw new \Exception("Controller {$handlerClass} not found");
        }

        $handler = new $handlerClass();
        
        // Inject DI container
        if ($handler instanceof \Core\Di\Injectable) {
            $handler->setDI($this->getDI());
        }

        // Inject EventsManager
        if ($handler instanceof \Core\Events\EventAware) {
            $handler->setEventsManager($this->getEventsManager());
        }

        // Initialize controller
        if (method_exists($handler, 'initialize')) {
            $handler->initialize();
        }

        // Check if action exists
        $actionMethod = $action . 'Action';
        if (!method_exists($handler, $actionMethod)) {
            throw new \Exception("Action {$actionMethod} not found in {$handlerClass}");
        }

        // Trigger beforeExecuteRoute event
        $this->fireEvent('core:beforeExecuteRoute', $handler);

        // Execute the action
        $response = call_user_func_array([$handler, $actionMethod], $params);

        // Call afterExecute method if exists
        if (method_exists($handler, 'afterExecute')) {
            $handler->afterExecute();
        }
        

        // Get the response from the controller
        $finalResponse = $handler->getResponse() ?? $response;

        // Handle array responses
        if (is_array($finalResponse)) {
            header('Content-Type: application/json');
            $finalResponse = json_encode($finalResponse);
        }

        // Handle view objects
        if ($finalResponse instanceof \Core\View\ViewInterface) {
            $finalResponse = $finalResponse->render();
        }

        // Handle objects with __toString method
        if (is_object($finalResponse) && method_exists($finalResponse, '__toString')) {
            $finalResponse = (string)$finalResponse;
        }

        // Output the response
        if ($finalResponse !== null) {
            echo $finalResponse;
        }
    }

    public function getActionName(): string
    {
        return $this->actionName;
    }

    public function getControllerName(): string
    {
        return $this->controllerName;
    }
}