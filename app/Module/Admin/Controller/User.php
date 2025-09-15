<?php
namespace Module\Admin\Controller;

use Core\Mvc\Controller;

class User extends Controller
{
    public function getAction($id): array
    {
        $user = \Module\Admin\Models\Users::find($id);
        if (null === $user) {

        }
        
        
        var_dump($user);
        exit;

    }
    
    public function aboutAction()
    {
        // Manual view rendering with custom template
        $this->view->setVar('title', 'About Us');
        $this->view->setVar('content', 'This is the about page.');
        
        return $this->view->render('module/base/index/custom-about');
    }
    
    public function apiAction()
    {
        // Disable auto-render and return JSON
        
        return json_encode([
            'status' => 'success',
            'data' => ['message' => 'API response']
        ]);
    }
}