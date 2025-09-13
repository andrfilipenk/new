<?php
// app/Module/Base/Controller/Index.php
namespace Module\Base\Controller;

use Core\Mvc\Controller;

class Index extends Controller
{
    public function indexAction(): array
    {
        // Return data to be passed to the view (auto-rendered)
        return [
            'title' => 'Home Page',
            'name' => 'John Doe',
            'users' => [
                ['name' => 'Alice'],
                ['name' => 'Bob'],
                ['name' => 'Charlie']
            ]
        ];
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
        $this->disableAutoRender();
        
        return json_encode([
            'status' => 'success',
            'data' => ['message' => 'API response']
        ]);
    }
    
    public function manualAction()
    {
        // Manual response handling
        $this->setResponse("Direct response without view");
    }
    
    public function jsonAction()
    {
        // Use the built-in JSON helper
        $this->json([
            'status' => 'success',
            'data' => ['message' => 'JSON response']
        ]);
    }
    
    public function afterExecute(): void
    {
        // This is called after the action executes
        // You can modify the response here if needed
        parent::afterExecute();
    }
}