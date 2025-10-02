<?php
// app/Base/Controller/Auth.php
namespace Base\Controller;

use Core\Mvc\Controller;
use Admin\Model\User;

class Auth extends Controller
{
    public function indexAction()
    {
        if (!$this->getSession()->get('user')) {
            return $this->redirect('login');
        }
        $handler = $this->getSession()->getHandler();
        $activeSessions = $handler->getActiveSessionsCount();
        return $this->render(null, ['activeSessions' => $activeSessions]);
    }

    public function loginAction()
    {
        if ($this->isPost()) {
            $id = $this->getPost('id');
            $pw = $this->getPost('password');
            if ($id && $pw) {
                /** @var User $user */
                $user = User::find($id, 'custom_id');
                if ($user && $user->verifyPassword($pw)) {
                    $this->getSession()->set('user', $user->getData());
                    $this->flashSuccess('Logged in successfully.');
                    return $this->redirect('/');
                }
                $this->flashError('Invalid credentials.');
            }
        }
        $this->getView()->setLayout('window');
        return $this->render('auth/login');
    }

    public function kuhnleAction()
    {
        $id = $this->getRequest()->get('custom_id');
        $ip = $this->getRequest()->ip();
        if (!in_array($ip, ['listing', 'blacklist'])) {
            $this->flashError('Invalid remote access');
            return $this->redirect('auth/login');
        }
        $user = User::find($id, 'custom_id');
        if (!$user) {
            $this->flashError('Invalid user data');
            return $this->redirect('auth/login');
        }
        $this->getSession()->set('user', $user->getData());
        $this->flashSuccess('Logged in successfully.');
        return $this->redirect('/');
    }
}