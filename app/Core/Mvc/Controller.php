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
    
    public function getView()
    {
        if (null === $this->view) {
            /** @var \Core\Mvc\View $view */
            $view = $this->getDI()->get('view');
            $this->view = $view;
        }
        return $this->view;
    }

    public function getRequest()
    {
        /** @var \Core\Http\Request $request */
        $request = $this->getDI()->get('request');
        return $request;
    }


    public function isPost()
    {
        return $this->getRequest()->isMethod('post');
    }


    public function getPost($key = null, $default = null)
    {
        return $this->getRequest()->post($key, $default);
    }

    protected function render(string $template = null, array $data = []): string
    {
        if ($template !== null) {
            $dispatcher     = $this->getDI()->get('dispatcher');
            $calledClass    = $dispatcher->getControllerName();
            $parts          = explode('\\', strtolower($calledClass));
            $template       = 'module' . DIRECTORY_SEPARATOR . $parts[1] . DIRECTORY_SEPARATOR . $template;
        } else {
            $template = $this->getTemplateName();
        }
        return $this->getView()->render($template, $data);
    }
    
    protected function redirect(string $to, int $statusCode = 302): Response
    {
        $url = $this->getDI()->get('url');
        return Response::redirect($url->get($to), $statusCode)->send();
    }

    public function flashSuccess($message)
    {
        $this->getDI()->get('session')->flash('success', $message);
        return $this;
    }

    public function flashError($message)
    {
        $this->getDI()->get('session')->flash('error', $message);
        return $this;
    }

    protected function getTemplateName(): string
    {
        $dispatcher = $this->getDI()->get('dispatcher');
        $calledClass = $dispatcher->getControllerName();
        $action     = $dispatcher->getActionName();
        $parts      = explode('\\', $calledClass);
        $module     = 'module' . DIRECTORY_SEPARATOR . strtolower($parts[1]) . DIRECTORY_SEPARATOR;
        $controller = strtolower(str_replace('Controller', '', end($parts)));
        return $module . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . $action;
    }
}