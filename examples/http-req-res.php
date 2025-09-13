<?php
$di = new Core\Di\Container();
// 3. Integration with DI Container:
// Register request service (shared instance)
$di->set('request', function() {
    return new Core\Http\Request();
});

// Register response service (new instance each time)
$di->set('response', function() {
    return new Core\Http\Response();
});




// Basic Usage in Controllers:


use Core\Http\Request;
use Core\Http\Response;

class UserController
{
    public $userService;
    public $view;
    public function indexAction(Request $request, Response $response)
    {
        // Get query parameters
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);
        
        // Get users from database
        $users = $this->userService->getUsers($page, $limit);
        
        // Return JSON response
        return $response->json([
            'success' => true,
            'users' => $users
        ]);
    }
    
    public function createAction(Request $request, Response $response)
    {
        // Check if it's a POST request
        if (!$request->isPost()) {
            return $response->setStatusCode(405)->setContent('Method Not Allowed');
        }
        
        // Get POST data
        $name = $request->post('name');
        $email = $request->post('email');
        
        // Validate and create user
        $user = $this->userService->createUser($name, $email);
        
        if ($user) {
            // Redirect to user page
            return $response->redirect("/user/{$user['id']}");
        }
        
        // Return error response
        return $response->setStatusCode(400)->json([
            'success' => false,
            'error' => 'Failed to create user'
        ]);
    }
    
    public function showAction(Request $request, Response $response, $id)
    {
        // Get user by ID
        $user = $this->userService->getUser($id);
        
        if (!$user) {
            return $response->notFound('User not found');
        }
        
        // Render view
        $content = $this->view->render('user/show', ['user' => $user]);
        
        return $response->setContent($content);
    }
}




// Middleware Example:

class AuthMiddleware
{
    public $auth;
    public function handle(Request $request, Response $response)
    {
        // Check if user is authenticated
        if (!$this->auth->check()) {
            // Redirect to login page
            return $response->redirect('/login');
        }
        
        // Continue to next middleware/controller
        return true;
    }
}

class CorsMiddleware
{
    public function handle(Request $request, Response $response)
    {
        // Set CORS headers
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type');
        
        // Handle preflight requests
        if ($request->method() === 'OPTIONS') {
            $response->setStatusCode(200)->send();
            exit;
        }
        
        return true;
    }
}






// API Controller Example:

// namespace App\Controllers\Api;


class UserApiController
{

    public $userService;

    public function index(Request $request, Response $response)
    {
        // Check if request is AJAX
        if (!$request->isAjax()) {
            return $response->setStatusCode(400)->json([
                'error' => 'This endpoint requires AJAX requests'
            ]);
        }
        
        // Get all users
        $users = $this->userService->getAllUsers();
        
        return $response->json([
            'success' => true,
            'data' => $users
        ]);
    }
    
    public function store(Request $request, Response $response)
    {
        // Validate required fields
        $required = ['name', 'email'];
        foreach ($required as $field) {
            if (!$request->post($field)) {
                return $response->setStatusCode(400)->json([
                    'error' => "Missing required field: $field"
                ]);
            }
        }
        
        // Create user
        $user = $this->userService->createUser(
            $request->post('name'),
            $request->post('email')
        );
        
        if ($user) {
            return $response->setStatusCode(201)->json([
                'success' => true,
                'data' => $user
            ]);
        }
        
        return $response->setStatusCode(500)->json([
            'error' => 'Failed to create user'
        ]);
    }
}