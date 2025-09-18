<?php
// app/Core/Mvc/Controller.php
namespace Core\Mvc;

use Core\Di\Injectable;
use Core\Events\EventAware;
use Core\Http\Response;
use Core\Mvc\ViewInterface;

class Controller
{
    use Injectable, EventAware;
    
    /** @var \Core\Mvc\View $view */
    protected $view;

        
    // public function initialize() {}
    // public function afterExecute() {}

    /**
     * Returns view instance
     *
     * @return \Core\Mvc\View
     */
    public function getView()
    {
        if (null === $this->view) {
            $this->view = $this->getDI()->get('view');
        }
        return $this->view;
    }

    /**
     * Returns request object
     *
     * @return \Core\Http\Request
     */
    public function getRequest()
    {
        return $this->getDI()->get('request');
    }

    /**
     * Check if method is post
     *
     * @return boolean
     */
    public function isPost()
    {
        return $this->getRequest()->isMethod('post');
    }

    /**
     * Returns post data
     *
     * @param [type] $key
     * @param [type] $default
     * @return mixed
     */
    public function getPost($key = null, $default = null)
    {
        return $this->getRequest()->post($key, $default);
    }
    
    /**
     * Renders a view. If data is returned from an action, it's passed to the view.
     * This is now a helper method. The result must be returned by the action.
     *
     * @return string The rendered view content.
     */
    protected function render(string $template = null, array $data = []): string
    {
        if ($template !== null) {
            $dispatcher = $this->getDI()->get('dispatcher');
            $calledClass = $dispatcher->getControllerName();
            $parts = explode('\\', strtolower($calledClass));
            $template = 'module' . DIRECTORY_SEPARATOR . $parts[1] . DIRECTORY_SEPARATOR . $template;
        } else {
            $template = $this->getTemplateName();
        }
        return $this->getView()->render($template, $data);
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

        $module = 'module' . DIRECTORY_SEPARATOR . strtolower($parts[1]) . DIRECTORY_SEPARATOR;
        $controller = strtolower(str_replace('Controller', '', end($parts)));
        
        return $module . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . $action;
    }
}