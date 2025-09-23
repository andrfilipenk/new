<?php
// app/Core/Mvc/Dispatcher.php
namespace Core\Mvc;

use Core\Di\Injectable;
use Core\Events\EventAware;
use Core\Http\Request;
use Core\Http\Response;

class Dispatcher
{
    use Injectable, EventAware;

    protected $moduleName;
    protected $actionName;
    protected $controller;
    protected $controllerName;
    protected $params = [];

    public function dispatch(array $route, Request $request)
    {
        $this->moduleName = $route['module'];
        $this->controllerName = $route['controller'];
        $this->actionName = $route['action'];
        $this->params = $route['params'] ?? [];

        $eventsManager = $this->getDI()->get('eventsManager');
        $eventsManager->trigger('core:beforeDispatch', $this);

        $handlerClass = $this->getHandlerClass();
        if (!class_exists($handlerClass)) {
            throw new \Exception("Controller {$handlerClass} not found");
        }
        // Use the DI container to build the controller, enabling autowiring
        $handler = $this->getDI()->get($this->getHandlerClass());
        if (method_exists($handler, 'initialize')) {
            $handler->initialize();
        }
        $actionMethod = $this->actionName . 'Action';
        if (!method_exists($handler, $actionMethod)) {
            throw new \Exception("Action {$actionMethod} not found in {$handlerClass}");
        }
        $eventsManager->trigger('core:beforeExecuteRoute', $handler);
        // Execute the action and get the return value
        $responseContent = call_user_func([$handler, $actionMethod]);
        if (method_exists($handler, 'afterExecute')) {
            $handler->afterExecute();
        }
        $eventsManager->trigger('core:afterDispatch', $this);
        // If the controller action returns a full Response object, use it directly
        if ($responseContent instanceof Response) {
            return $responseContent;
        }
        // If the controller returns an array, create a JSON response
        if (is_array($responseContent)) {
            return Response::json($responseContent);
        }
        return new Response($responseContent);
    }

    public function getHandlerClass()
    {
        return sprintf(
            "Module\\%s\\Controller\\%s", 
            ucfirst($this->moduleName),
            ucfirst($this->controllerName)
        );
    }

    public function setControllerName($name) {
        $this->controllerName = $name;
        return $this;
    }

    public function setModuleName($name)
    {
        $this->moduleName = $name;
        return $this;
    }
    
    public function setActionName($name)
    {
        $this->actionName = $name;
        return $this;
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

    public function getModuleName(): string
    {
        return $this->moduleName;
    }
}