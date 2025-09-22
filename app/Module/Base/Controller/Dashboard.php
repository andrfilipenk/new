<?php
// app/Module/Base/Controller/Dashboard.php
namespace Module\Base\Controller;

use Core\Mvc\Controller;

class Dashboard extends Controller
{
    public function indexAction()
    {
        $this->flashSuccess('This is success Message');

        // Prepare navigation items
        
        // Return the data, and the framework will handle rendering
        $data = [
            'title' => 'Dashboard - Our Framework',
            'brand' => 'Our Framework',
            'user_name' => 'John Doe',
            'welcome' => 'Welcome to our framework',
            'stats' => [
                'users' => 150,
                'orders' => 342,
                'revenue' => '$12,432'
            ]
        ];

        return $this->render(null, $data);
    }
}