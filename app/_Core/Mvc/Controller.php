<?php
// app/_Core/Mvc/Controller.php
namespace Core\Mvc;

use Core\Di\Injectable;
use Core\Events\EventAware;

class Controller
{
    use Injectable, EventAware;
        
    public function initialize() {
        $dispatcher = $this->getDispatcher();
        $viewPath   = APP_PATH . $dispatcher->getModule() . '/views/';
        $this->getView()->setTemplatePath($viewPath);
    }

    public function beforeExecute() {}
    public function afterExecute() {}
    
    /**
     * Returns view instance
     *
     * @return \Core\Mvc\View
     */
    public function getView()
    {
        return $this->getDI()->get('\Core\Mvc\View');
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
     * Returns response instance
     *
     * @return \Core\Http\Response
     */
    public function getResponse()
    {
        return $this->getDI()->get('response');
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
     * Returns dispatcher instance
     *
     * @return \Core\Mvc\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->getDI()->get('\Core\Mvc\Dispatcher');
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

    /**
     * Check if ajax request
     * 
     * @return bool
     */
    public function isAjax()
    {
        return $this->getRequest()->isAjax();
    }

    /**
     * Returns true if request is post
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->getRequest()->isMethod('post');
    }

    /**
     * Returns post value
     *
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed
     */
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

    /**
     * Redirects to other url
     * 
     * @param string $to
     * @param int $statusCode
     * @return \Core\Http\Response
     */
    protected function redirect($to, int $statusCode = 302)
    {
        return $this->getResponse()->redirect($this->url($to), $statusCode);
    }

    /**
     * Forward to other controller / action
     *
     * @param array $route
     * @return Dispatcher
     */
    protected function forward($route = []) 
    {
        $dispatcher = $this->getDispatcher();
        $dispatcher->forward($route);
        return $dispatcher;
    }

    /**
     * Render template with vars
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    protected function render(string $template = null, array $data = []): string
    {
        if ($template === null) {
            $dispatcher = $this->getDispatcher();
            $template = $dispatcher->getController() . '/' . $dispatcher->getAction();
        }
        return $this->getView()->render($template, $data);
    }
}