<?php
// app/Core/Mvc/Controller.php
namespace Core\Mvc;

use Core\Di\Injectable;
use Core\Events\EventAware;
use Core\Http\Response;

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
     * @return \Core\Mvc\View $view
     */
    public function getView()
    {
        if (null === $this->view) {
            $view = $this->getDI()->get('view');
            $this->view = $view;
        }
        return $this->view;
    }

    /**
     * Returns request instance
     *
     * @return \Core\Http\Request
     */
    public function getRequest()
    {
        return $this->getDI()->get('request');
    }

    /**
     * Returns session instance
     *
     * @return \Core\Session\Session
     */
    public function getSession()
    {
        return $this->getDI()->get('session');
    }

    /**
     * Returns url instance or url
     *
     * @param string|null $to
     * @return \Core\Utils\Url|string
     */
    protected function url($to = null)
    {
        /** @var \Core\Utils\Url $url */
        $url = $this->getDI()->get('url');
        if ($to === null) {
            return $url;
        }
        return $url->get($to);
    }

    public function isPost()
    {
        return $this->getRequest()->isMethod('post');
    }

    public function getPost($key = null, $default = null)
    {
        return $this->getRequest()->post($key, $default);
    }

    public function flashSuccess($message)
    {
        $this->getSession()->flash('success', $message);
        return $this;
    }

    public function flashError($message)
    {
        $this->getSession()->flash('error', $message);
        return $this;
    }

    protected function redirect(string $to, int $statusCode = 302): Response
    {
        return Response::redirect($this->url($to), $statusCode)->send();
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