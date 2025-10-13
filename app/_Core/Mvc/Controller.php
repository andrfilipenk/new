<?php
// app/_Core/Mvc/Controller.php
namespace Core\Mvc;

use Core\Di\Injectable;

class Controller
{
    use Injectable;

    /**
     * Initializes as first
     *
     * @return $this
     */
    public function initialize() {
        return $this;
    }

    /**
     * Extendable by child-class
     *
     * @return $this
     */
    public function beforeExecute() {
        return $this;
    }

    /**
     * Extendable by child-class
     *
     * @return $this
     */
    public function afterExecute() {
        return $this;
    }

    /**
     * Check csrf token
     *
     * @return bool
     */
    protected function validateCsrfToken()
    {
        if ($this->isPost()) {
            $token = $this->getRequest()->post('_token');
            $sessionToken = $this->getSession()->get('csrf_token');
            return $token && $sessionToken && hash_equals($sessionToken, $token);
        }
        return true;
    }
    
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
     * Returns validator instance
     *
     * @return \Core\Validation\Validator
     */
    public function getValidator()
    {
        return $this->getDI()->get('\Core\Validation\Validator');
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

    /**
     * Set message to session
     *
     * @param string $type
     * @param string $message
     * @return $this
     */
    public function flashMessage(string $type, string $message)
    {
        $messages = $this->getSession()->get('messages', []);
        $messages[] = [
            'type'      => $type,
            'message'   => $message
        ];
        $this->getSession()->set('messages', $messages);
        return $this;
    }

    /**
     * Set success message
     *
     * @param string $message
     * @return $this
     */
    public function flashSuccess(string $message)
    {
        return $this->flashMessage('success', $message);
    }

    /**
     * Set error message
     *
     * @param string $message
     * @return $this
     */
    public function flashError(string $message)
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
        $dispatcher = $this->getDispatcher();
        if ($template === null) {
            $template = $dispatcher->getModule() . '/' . $dispatcher->getController() . '/' . $dispatcher->getAction();
        } else {
            $template = $dispatcher->getModule() . '/' . $template;
        }
        return $this->getView()->render($template, $data);
    }
}