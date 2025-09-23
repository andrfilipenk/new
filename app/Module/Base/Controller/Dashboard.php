<?php
// app/Module/Base/Controller/Dashboard.php
namespace Module\Base\Controller;

use Core\Mvc\Controller;
use Module\Admin\Models\User;

class Dashboard extends Controller
{
    public function indexAction()
    {
        // Check if user is logged in
        if (!$this->getSession()->get('user')) {
            return $this->redirect('login');
        }
        // Get session statistics
        $handler = $this->getSession()->getHandler();
        $activeSessions = $handler->getActiveSessionsCount();

        #$this->flashSuccess('This is success Message');
        $data = [
            'title' => 'Dashboard - Our Framework',
            'brand' => 'Our Framework',
            'user_name' => 'John Doe',
            'welcome' => 'Welcome to our framework',
            'stats' => [
                'users' => 150,
                'orders' => 342,
                'revenue' => '$12,432',
                'active_sessions' => $activeSessions
            ]
        ];
        return $this->render(null, $data);
    }

    public function loginAction()
    {
        $user = User::find(1);
        if ($user) {
            // Store user ID in session
            $this->getSession()->set('user', $user->getData());
            $this->flashSuccess('Login successful!');
        }
    }
}