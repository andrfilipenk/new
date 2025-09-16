<?php
// app/Core/Mvc/Dispatcher.php
namespace Core\Mvc;

use Core\Di\Interface\Container as ContainerInterface;
use Core\Di\Injectable;
use Core\Events\EventAware;
use Core\Http\Request;
use Core\Http\Response;

class Dispatcher
{
    use Injectable, EventAware;

    protected $actionName;
    protected $controllerName;
    protected $params = [];

    public function dispatch(array $route, Request $request)
    {
        $handlerClass = $route['controller'];
        $action = $route['action'];
        $params = $route['params'] ?? [];

        $this->actionName = $action;
        $this->controllerName = $handlerClass;
        $this->params = $params;

        $this->fireEvent('core:beforeDispatch', $this);

        if (!class_exists($handlerClass)) {
            throw new \Exception("Controller {$handlerClass} not found");
        }

        // Use the DI container to build the controller, enabling autowiring
        $handler = $this->getDI()->get($handlerClass);

        if (method_exists($handler, 'initialize')) {
            $handler->initialize();
        }

        $actionMethod = $action . 'Action';
        if (!method_exists($handler, $actionMethod)) {
            throw new \Exception("Action {$actionMethod} not found in {$handlerClass}");
        }

        $this->fireEvent('core:beforeExecuteRoute', $handler);

        // Execute the action and get the return value
        $responseContent = call_user_func([$handler, $actionMethod]);

        if (method_exists($handler, 'afterExecute')) {
            $handler->afterExecute();
        }

        // If the controller action returns a full Response object, use it directly
        if ($responseContent instanceof Response) {
            return $responseContent;
        }

        // If the controller returns an array, create a JSON response
        if (is_array($responseContent)) {
            return Response::json($responseContent);
        }

        // Otherwise, treat the return value as content for a standard HTML response
        return new Response($responseContent);
    }

    public function getParam($key, $default = null)
    {
        return isset($this->params[$key]) ? $this->params[$key] : $default;
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