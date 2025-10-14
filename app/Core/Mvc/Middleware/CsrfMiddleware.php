<?php
namespace Core\Mvc\Middleware;

use Core\Di\Interface\Container;

class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Container $di, string $module, string $controller, string $action): bool
    {
        if ($di->get('request')->isPost()) {
            $session = $di->get('session');
            $token = $di->get('request')->get('_csrf');
            if (!$session->has('_csrf') || $session->get('_csrf') !== $token) {
                $di->get('response')->setStatusCode(403)->setContent('Invalid CSRF token');
                return false;
            }
        }
        return true;
    }
}