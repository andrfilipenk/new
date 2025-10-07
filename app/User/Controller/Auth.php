<?php
// app/User/Controller/Auth.php
namespace User\Controller;

use Core\Mvc\Controller;
use User\Model\User;

class Auth extends Controller
{
    public function indexAction()
    {
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
                    $this->flashSuccess(message: 'Logged in successfully.');
                    return $this->redirect('user/profile');
                }
                $this->flashError('Invalid credentials.');
            }
        }
        $this->getView()->setLayout('window');
        return $this->render('auth/login');
    }

    public function logoutAction()
    {
        $this->getSession()->destroy();
        $this->flashSuccess('Logged out successfully.');
        return $this->redirect('user/login');
    }

    protected function authByKuhnle()
    {
        $id = $this->getRequest()->get('custom_id');
        $ip = $this->getRequest()->ip();
        if (!in_array($ip, ['listing', 'blacklist'])) {
            $user = User::find($id, 'custom_id');
            if ($user) {
                $this->flashSuccess('Logged in successfully by Kuhnle-App.');
                $this->getSession()->set('user', $user->getData());
                return true;
            }
        }
        return false;
    }
}