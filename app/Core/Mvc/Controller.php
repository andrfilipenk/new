<?php
namespace Core\Mvc;

use Core\Di\Injectable;
use Core\Events\EventAware;
use Core\View\ViewInterface;

class Controller
{
    use Injectable, EventAware;
    
    protected $view;
    protected $autoRender = true;
    protected $response;
    protected $actionReturnValue;
    
    public function initialize(): void
    {
        // Initialize view if available
        if ($this->getDI()->has('view')) {
            $this->view = $this->getDI()->get('view');
        }
    }
    
    public function afterExecute(): void
    {
        // Auto-render if enabled and no response set yet
        if ($this->autoRender && $this->response === null && $this->view !== null) {
            // If we have view data from the action return, use it
            if (is_array($this->actionReturnValue)) {
                $this->view->setVars($this->actionReturnValue);
            }
            $this->response = $this->view->render($this->getTemplateName());
        }
    }

    public function json($data, int $options = 0, int $depth = 512): void
    {
        $this->disableAutoRender();
        header('Content-Type: application/json');
        $this->setResponse(json_encode($data, $options, $depth));
    }

    public function setActionReturnValue($value): void
    {
        $this->actionReturnValue = $value;
    }
    
    protected function getTemplateName(): string
    {
        // Get called class and action
        $calledClass = get_called_class();
        $action = $this->getDI()->get('dispatcher')->getActionName();
        
        // Convert controller class name to template path
        $module = '';
        
        if (strpos($calledClass, 'Module\\') === 0) {
            $parts = explode('\\', $calledClass);
            $module = strtolower($parts[1]) . DIRECTORY_SEPARATOR;
        }
        
        $controller = str_replace('Controller', '', $parts[3] ?? end($parts));
        $controller = strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $controller));
        
        return $module . $controller . DIRECTORY_SEPARATOR . $action;
    }
    
    public function render(string $template = null, array $data = [])
    {
        if ($this->view === null) {
            throw new \Exception("View service is not available");
        }
        
        if ($template !== null) {
            return $this->view->render($template, $data);
        }
        
        return $this->view->render($this->getTemplateName(), $data);
    }
    
    public function disableAutoRender(): void
    {
        $this->autoRender = false;
    }
    
    public function enableAutoRender(): void
    {
        $this->autoRender = true;
    }
    
    public function setResponse($response): void
    {
        $this->response = $response;
    }
    
    public function getResponse()
    {
        return $this->response;
    }
    
    public function forward(string $controller, string $action, array $params = [])
    {
        return [
            'controller' => $controller,
            'action' => $action,
            'params' => $params
        ];
    }
    
    public function redirect(string $url, int $statusCode = 302): void
    {
        header("Location: $url", true, $statusCode);
        exit;
    }
}