<?php
// app/Module/Base/Controller/Dashboard.php
namespace Module\Base\Controller;

use Core\Mvc\Controller;

class Dashboard extends Controller
{
    public function indexAction()
    {
        /** @var \Core\Mvc\View $view */
        $view = $this->getDI()->get('view');
        $view->setLayout('app');
        // Prepare navigation items
        $navigation = [
            [
                'text' => 'Dashboard',
                'url' => '/dashboard',
                'active' => true
            ],
            [
                'text' => 'Users',
                'url' => '/users',
            ],
            [
                'text' => 'Settings',
                'url' => '/settings',
            ],
            [
                'text' => 'Reports',
                'url' => '/reports',
            ]
        ];
        
        // Prepare page data
        $data = [
            'title' => 'Dashboard - Our Framework',
            'brand' => 'Our Framework',
            'user_name' => 'John Doe',
            'navigation' => $navigation,
            'content' => [
                'welcome' => 'Welcome to our framework',
                'stats' => [
                    'users' => 150,
                    'orders' => 342,
                    'revenue' => '$12,432'
                ]
            ]
        ];
        
        return $view->render('module/dashboard/index');
    }
}