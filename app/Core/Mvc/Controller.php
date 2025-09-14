<?php
namespace Core\Mvc;

use Core\Di\Injectable;
use Core\Events\EventAware;
use Core\Http\Response;
use Core\View\ViewInterface;

class Controller
{
    use Injectable, EventAware;
    
    protected $view;
    
    public function initialize(): void
    {
        if ($this->getDI()->has('view')) {
            $this->view = $this->getDI()->get('view');
        }
    }
    
    /**
     * Renders a view. If data is returned from an action, it's passed to the view.
     * This is now a helper method. The result must be returned by the action.
     *
     * @return string The rendered view content.
     */
    protected function render(string $template = null, array $data = []): string
    {
        if ($this->view === null) {
            throw new \Exception("View service is not available");
        }
        
        $template = $template ?? $this->getTemplateName();
        return $this->view->render($template, $data);
    }

    /**
     * Creates a redirect response. This must be returned by the action.
     *
     * @return Response
     */
    protected function redirect(string $url, int $statusCode = 302): Response
    {
        return Response::redirect($url, $statusCode);
    }
    
    protected function getTemplateName(): string
    {
        $dispatcher = $this->getDI()->get('dispatcher');
        $calledClass = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();
        
        $parts = explode('\\', $calledClass);
        $module = strtolower($parts[1]);
        $controller = strtolower(str_replace('Controller', '', end($parts)));
        
        return $module . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . $action;
    }
}