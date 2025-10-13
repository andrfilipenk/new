<?php
// app/Core/Mvc/Dispatcher.php
namespace Core\Mvc;

use Core\Di\Injectable;
use Core\Exception\DispatchException;

class Dispatcher
{
    use Injectable;

    /**
     * @var string
     */
    protected $module;

    /**
     * @var string
     */
    protected $action;

    /**
     * @var string
     */
    protected $controller;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var boolean
     */
    protected $forwarded = false;

    /**
     * Prepare data
     *
     * @param array $route
     * @return $this
     */
    public function prepare($route)
    {
        $this->setModule($route['module'])
            ->setController($route['controller'])
            ->setAction($route['action'])
            ->setParams($route['params']);
        return $this;
    }

    /**
     * Forward route
     *
     * @param array $route
     * @return $this
     */
    public function forward($route)
    {
        if ($this->forwarded) {
            throw new DispatchException("Allready forwareded action", '', [
                'forwarded' => true,
                'route_to' => $route
            ]);
        }
        $this->prepare($route);
        $this->forwarded = true;
        return $this;
    }

    /**
     * Returns instance of controller
     *
     * @return \Core\Mvc\Controller
     */
    public function getControllerInstance()
    {
        if (!class_exists($this->getControllerClass())) {
            throw new DispatchException("Controller {$this->getControllerClass()} not found", '', [
                'module'        => $this->getModule(),
                'controller'    => $this->getControllerClass()
            ]);
        }
        /** @var \Core\Mvc\Controller $controller */
        $controller = $this->getDI()->get($this->getControllerClass());
        return $controller;
    }

    /**
     *
     * @return \Core\Events\Manager
     */
    public function getEventsManager()
    {
        return $this->getDI()->get('eventsManager');
    }

    /**
     * Dispatche route
     *
     * @return string|array|object
     */
    public function dispatch()
    {
        $this->getControllerInstance()->initialize();
        $this->getEventsManager()->trigger('dispatcher.beforeExecute', $this);
        $this->getControllerInstance()->beforeExecute();

        if (!method_exists($this->getControllerInstance(), $this->getActionMethod())) {
            throw new DispatchException("Action {$this->getActionMethod()} not found in Controller {$this->getController()}", '', [
                'module'        => $this->getModule(),
                'controller'    => $this->getController(),
                'action'        => $this->getActionMethod()
            ]);
        }

        $result = call_user_func([$this->getControllerInstance(), $this->getActionMethod()]);
        $this->getControllerInstance()->afterExecute();
        $this->getEventsManager()->trigger('dispatcher.afterExecute', $this);
        
        if ($result instanceof Dispatcher) {
            $result = $result->dispatch();
        }
        return $result;
    }

    /**
     *
     * @param string $module
     * @return $this
     */
    public function setModule(string $module)
    {
        $this->module = $module;
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getModule(): string
    {
        return $this->module;
    }

    /**
     *
     * @param string $controller
     * @return $this
     */
    public function setController(string $controller) {
        $this->controller = $this->getModule() . '\\Controller\\' . $controller;
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getController(): string
    {
        return $this->controller;
    }

    public function getControllerClass() {
        return $this->getController() . 'Controller';
    }

    /**
     *
     * @param string $action
     * @return $this
     */
    public function setAction(string $action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * 
     * @return string
     */
    public function getActionMethod() 
    {
        return $this->getAction() . 'Action';
    }

    /**
     *
     * @param array $params
     * @return $this
     */
    public function setParams(array $params) 
    {
        $this->params = $params;
        return $this;
    }

    /**
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getParam(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }
}