<?php
// app/Core/Mvc/Dispatcher.php
namespace Core\Mvc;

use Core\Di\Injectable;
use Core\Events\EventAware;

class Dispatcher
{
    use Injectable, EventAware;

    protected $module;
    protected $action;
    protected $controller;
    protected $params = [];

    protected $forwarded = false;

    /**
     * Prepare data
     *
     * @param array $route
     * @return $this
     */
    public function prepare($route)
    {
        $ns = $route['module'] . '\\Controller\\';
        $this->setModule($route['module'])
            ->setController($ns . $route['controller'])
            ->setAction($route['action'])
            ->setParams($route['params']);
        return $this;
    }

    /**
     * Forwarding route
     *
     * @param array $route
     * @return $this
     */
    public function forward($route)
    {
        if ($this->forwarded) {
            throw new \Exception("Allready forwareded action");
        }
        $this->prepare($route);
        $this->forwarded = true;
        return $this;
    }

    /**
     * Dispatche route
     *
     * @return string|array|object
     */
    public function dispatch()
    {
        if (!class_exists($this->getController())) {
            throw new \Exception("Controller {$this->getController()} not found");
        }

        /** @var \Core\Mvc\Controller $controller */
        $controller = $this->getDI()->get($this->getController());
        if (!method_exists($controller, $this->getActionMethod())) {
            throw new \Exception("Action {$this->getActionMethod()} not found in Controller {$this->getController()}");
        }
        $controller->initialize();
        $controller->beforeExecute();
        $this->fireEvent('dispatcher:beforeExecute', $this);

        $result = call_user_func([$controller, $this->getActionMethod()]);
        $controller->afterExecute();
        $this->fireEvent('dispatcher:afterExecute', $this);

        if ($result instanceof Dispatcher) {
            $result = $result->dispatch();
        }
        return $result;
    }

    public function setModule($module)
    {
        $this->module = $module;
        return $this;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function setController($controller) {
        $this->controller = $controller;
        return $this;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getActionMethod() 
    {
        return $this->getAction() . 'Action';
    }

    public function setParams($params) 
    {
        $this->params = $params;
        return $this;
    }

    public function getParam($key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }
}