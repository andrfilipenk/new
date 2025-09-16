<?php
// app/Module/Base/Controller/User.php
namespace Module\Base\Controller;

use Core\Mvc\Controller;
use stdClass;

class User extends Controller
{
    protected function authenticate($username,$password) {
        return new stdClass;
    }
    public function rememberMeAction()
    {
        if ($this->request->isPost()) {
            $username = $this->request->getPost('username');
            $password = $this->request->getPost('password');
            $remember = $this->request->getPost('remember', false);
            
            // Authenticate user (pseudo-code)
            if ($user = $this->authenticate($username, $password)) {
                // Store user in session
                $this->session->set('user', [
                    'id' => $user->id,
                    'username' => $username,
                    'role' => $user->role
                ]);
                
                // Set remember me cookie if requested
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    
                    // Store token in database (pseudo-code)
                    $this->userModel->updateRememberToken($user->id, $token);
                    
                    // Set secure cookie
                    $this->cookie->setPersistent('remember_me', $token, [
                        'httponly' => true,
                        'secure' => true, // Only over HTTPS
                        'samesite' => 'Strict'
                    ]);
                }
                
                $this->session->flash('success', 'Login successful!');
                return $this->redirect('/dashboard');
            } else {
                $this->session->flash('error', 'Invalid credentials');
                return $this->view->render('user/login');
            }
        }
        
        return $this->view->render('user/login');
    }
    
    public function preferencesAction()
    {
        // Get user preferences from cookie
        $theme = $this->cookie->get('theme', 'light');
        $language = $this->cookie->get('language', 'en');
        $itemsPerPage = $this->cookie->get('items_per_page', 10);
        
        if ($this->request->isPost()) {
            // Save preferences to cookies
            $theme = $this->request->getPost('theme', 'light');
            $language = $this->request->getPost('language', 'en');
            $itemsPerPage = $this->request->getPost('items_per_page', 10);
            
            $this->cookie->set('theme', $theme);
            $this->cookie->set('language', $language);
            $this->cookie->set('items_per_page', $itemsPerPage);
            
            $this->session->flash('success', 'Preferences saved!');
            return $this->redirect('/preferences');
        }
        
        return $this->view->render('user/preferences', [
            'theme' => $theme,
            'language' => $language,
            'itemsPerPage' => $itemsPerPage
        ]);
    }
    
    public function logoutAction()
    {
        // Clear session
        $this->session->remove('user');
        
        // Clear remember me cookie
        if ($this->cookie->has('remember_me')) {
            // Also clear from database (pseudo-code)
            $this->userModel->clearRememberToken($this->session->get('user')['id']);
            
            // Delete cookie
            $this->cookie->delete('remember_me');
        }
        
        $this->session->flash('success', 'You have been logged out');
        return $this->redirect('/login');
    }
}