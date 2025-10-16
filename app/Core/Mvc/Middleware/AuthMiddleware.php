<?php
namespace Core\Mvc\Middleware;

use Core\Di\Interface\Container;
use User\Model\Auth;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Container $di, string $module, string $controller, string $action): bool
    {
        $publicRoutes = $di->get('config')['acl']['public'] ?? [];
        foreach ($publicRoutes as [$m, $c, $a]) {
            if ($module === $m && $controller === $c && $action === $a) {
                return true; // Public route, no auth needed
            }
        }
        $auth = new Auth();
        if (!$auth->isLoggedIn()) {
            $di->get('response')->redirect('login')->send();
            return false;
        }
        
        // check access rights now

        return true;
    }
}