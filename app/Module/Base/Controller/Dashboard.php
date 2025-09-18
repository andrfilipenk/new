<?php
// app/Module/Base/Controller/Dashboard.php
namespace Module\Base\Controller;

use Core\Mvc\Controller;

class Dashboard extends Controller
{
    public function indexAction()
    {
        // Set the layout for this action
        $this->getDI()->get('view')->setLayout('app');

        // Prepare navigation items
        $navigation = [
            ['text' => 'Dashboard', 'url' => '/dashboard', 'active' => true],
            ['text' => 'Users', 'url' => '/users'],
            ['text' => 'Settings', 'url' => '/settings'],
            ['text' => 'Reports', 'url' => '/reports']
        ];
        
        // Return the data, and the framework will handle rendering
        return [
            'title' => 'Dashboard - Our Framework',
            'brand' => 'Our Framework',
            'user_name' => 'John Doe',
            'navigation' => $navigation,
            'welcome' => 'Welcome to our framework',
            'stats' => [
                'users' => 150,
                'orders' => 342,
                'revenue' => '$12,432'
            ]
        ];
    }
}