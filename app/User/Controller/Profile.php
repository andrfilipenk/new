<?php
// app/User/Controller/Profile.php
namespace User\Controller;

use Core\Mvc\Controller;
use User\Model\User as UserModel;

class Profile extends Controller
{
    /**
     * Get current logged-in user
     */
    protected function getCurrentUser()
    {
        $sessionUser = $this->getSession()->get('user');
        if (!$sessionUser) {
            $this->flashError('Please log in to access your profile.');
            return $this->redirect('user/login');
        }
        return UserModel::find($sessionUser['id']);
    }

    /**
     * View user profile
     */
    public function indexAction()
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return $this->redirect('user/login');
        }
        return $this->render('profile/index', [
            'user' => $user,
            'userGroups' => $user->groups
        ]);
    }

    /**
     * Edit user profile
     */
    public function editAction()
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return $this->redirect('user/login');
        }

        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            
            // Basic validation
            $name = trim($data['name'] ?? '');
            $email = trim($data['email'] ?? '');
            
            if (!$name || !$email) {
                $this->flashError('Name and email are required.');
            } else {
                $user->name = $name;
                $user->email = $email;
                
                if ($user->save()) {
                    // Update session data
                    $sessionUser = $this->getSession()->get('user');
                    $sessionUser['name'] = $name;
                    $sessionUser['email'] = $email;
                    $this->getSession()->set('user', $sessionUser);
                    
                    $this->flashSuccess('Profile updated successfully.');
                    return $this->redirect('user/profile');
                } else {
                    $this->flashError('Failed to update profile.');
                }
            }
        }

        return $this->render('profile/edit', ['user' => $user]);
    }

    /**
     * Change user password
     */
    public function passwordAction()
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return $this->redirect('user/login');
        }

        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            
            $currentPassword = $data['current_password'] ?? '';
            $newPassword = $data['new_password'] ?? '';
            $confirmPassword = $data['confirm_password'] ?? '';
            
            // Validate current password
            if (!$user->verifyPassword($currentPassword)) {
                $this->flashError('Current password is incorrect.');
            } elseif (strlen($newPassword) < 6) {
                $this->flashError('New password must be at least 6 characters long.');
            } elseif ($newPassword !== $confirmPassword) {
                $this->flashError('New password and confirmation do not match.');
            } else {
                $config = $this->getDI()->get('config');
                $algo = $config['app']['hash_algo'];
                $user->password = password_hash($newPassword, $algo);
                
                if ($user->save()) {
                    $this->flashSuccess('Password changed successfully.');
                    return $this->redirect('user/profile');
                } else {
                    $this->flashError('Failed to change password.');
                }
            }
        }

        return $this->render('profile/password', ['user' => $user]);
    }
}