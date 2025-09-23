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
     * @return \Core\Session\DatabaseSession
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

    public function flashMessage($type, $message)
    {
        $messages = $this->getSession()->get('messages', []);
        $messages[] = [
            'type'      => $type,
            'message'   => $message
        ];
        $this->getSession()->set('messages', $messages);
        return $this;
    }

    public function flashSuccess($message)
    {
        return $this->flashMessage('success', $message);
    }

    public function flashError($message)
    {
        return $this->flashMessage('error', $message);
    }

    protected function redirect(string $to, int $statusCode = 302): Response
    {
        $response = $this->getDI()->get('response');
        return $response->redirect($this->url($to), $statusCode)->send();
    }

    protected function render(string $template = null, array $data = []): string
    {
        if ($template !== null) {
            $dispatcher     = $this->getDI()->get('dispatcher');
            $module         = $dispatcher->getModuleName();

            $template       = 'module' . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . $template;
        } else {
            $template = $this->getTemplateName();
        }
        return $this->getView()->render($template, $data);
    }

    protected function getTemplateName(): string
    {
        $dispatcher = $this->getDI()->get('dispatcher');
        $moduleName = $dispatcher->getModuleName();
        $controller = $dispatcher->getControllerName();
        $action     = $dispatcher->getActionName();

        $path = 'module' . DIRECTORY_SEPARATOR 
            . $moduleName . DIRECTORY_SEPARATOR 
            . $controller . DIRECTORY_SEPARATOR 
            . $action;
        
        return $path;
    }
}