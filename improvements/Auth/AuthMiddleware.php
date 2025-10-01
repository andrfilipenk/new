<?php
// app/_Core/Auth/AuthMiddleware.php
namespace Core\Auth;

use Core\Http\Request;
use Core\Http\Response;
use Core\Mvc\Controller;
use Core\Di\Injectable;

class AuthMiddleware
{
    use Injectable;
    
    protected $publicRoutes = [
        'base/auth/login',
        'base/auth/logout',
        'base/error/denied',
        'base/error/notfound'
    ];
    
    public function handle(Request $request, Response $response, $next)
    {
        $session = $this->getDI()->get('session');
        $dispatcher = $this->getDI()->get('dispatcher');
        
        // Build current route
        $currentRoute = strtolower(implode('/', [
            $dispatcher->getModuleName(),
            $dispatcher->getControllerName(),
            $dispatcher->getActionName()
        ]));
        
        // Check if route is public
        if (in_array($currentRoute, $this->publicRoutes)) {
            return $next();
        }
        
        // Check if user is authenticated
        $user = $session->get('user');
        if (!$user) {
            if ($request->isAjax()) {
                return $response->json([
                    'error' => 'Authentication required',
                    'redirect' => '/login'
                ], 401);
            }
            return $response->redirect('/login');
        }
        
        // Check ACL permissions
        $access = $this->getDI()->get('access');
        $userRole = $user['role'] ?? 'user';
        
        if (!$access->isAllowed($userRole, 
            $dispatcher->getModuleName(),
            $dispatcher->getControllerName(), 
            $dispatcher->getActionName())) {
            
            if ($request->isAjax()) {
                return $response->json(['error' => 'Access denied'], 403);
            }
            return $dispatcher->forward(['controller' => 'error', 'action' => 'denied']);
        }
        
        return $next();
    }
}