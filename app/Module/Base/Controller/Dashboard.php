<?php
// app/Module/Base/Controller/Dashboard.php
namespace Module\Base\Controller;

use Core\Mvc\Controller;

class Dashboard extends Controller
{
    public function indexAction()
    {
        // Set the layout for this action
        // $this->getView()->setLayout('app');

        $url = $this->getDI()->get('url');

        $this->flashSuccess('This is success Message');

        // Prepare navigation items
        $navigation = [
            ['text' => 'Dashboard', 'url' => $url->get(''), 'active' => true],
            ['text' => 'Users', 'url' => '/users'],
            ['text' => 'Settings', 'url' => '/settings'],
            ['text' => 'Reports', 'url' => '/reports']
        ];
        
        // Return the data, and the framework will handle rendering
        $data = [
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

        return $this->render(null, $data);
    }
}