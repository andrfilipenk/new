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

    public function __construct(){}

    public function getRouteData($route)
    {
        if (isset($route['module'])) {
            $this->setModuleName($route['module']);
        }
        if (isset($route['controller'])) {
            $this->setControllerName($route['controller']);
        }
        if (isset($route['action'])) {
            $this->setActionName($route['action']);
        }
        if (isset($route['params'])) {
            $this->params = $route['params'];
        }
    }

    public function dispatch($route)
    {
        $this->getRouteData($route);

        $eventsManager = $this->getDI()->get('eventsManager');
        $handlerClass = $this->getHandlerClass();
        $eventsManager->trigger('core:beforeDispatch', $this);

        if (!class_exists($handlerClass)) {
            throw new \Exception("Controller {$handlerClass} not found");
        }

        $handler = $this->getDI()->get($handlerClass);   
        if (method_exists($handler, 'initialize')) {
            $handler->initialize();
        }

        $actionMethod = $this->actionName . 'Action';
        if (!method_exists($handler, $actionMethod)) {
            throw new \Exception("Action {$actionMethod} not found in {$handlerClass}");
        }

        $result = call_user_func([$handler, $actionMethod]);
        if (method_exists($handler, 'afterExecute')) {
            $handler->afterExecute();
        }
        
        return $result;
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