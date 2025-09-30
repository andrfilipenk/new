<?php
// app/Base/Controller/Auth.php
namespace Base\Controller;

use Core\Mvc\Controller;
use Admin\Model\User;

class Auth extends Controller
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
        if ($this->isPost()) {
            $id = $this->getPost('id');
            $pw = $this->getPost('password');
            if (null !== $id && null !== $pw) {
                $user = User::find($id, 'custom_id');
                if ($user) var_dump($user->name);
                if ($user->verifyPassword($pw)) var_dump("password ok");
                foreach ($user->createdTasks as $task) {
                    var_dump($task->creator);
                }


                exit;
            }
        }
        
        #if ($user)  $this->getSession()->set('user', $user->getData());
        $this->getView()->setLayout('window');
        return $this->render('auth/login');
    }

    public function kuhnleAction()
    {

    }
}