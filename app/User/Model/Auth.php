<?php
// app/User/Model/Auth.php
namespace User\Model;

use Core\Di\Injectable;

class Auth {

    use Injectable;

    /**
     * @return \Core\Session\DatabaseSession
     */
    protected function getSession()
    {
        return $this->getDI()->get('session');
    }

    /**
     *
     * @return boolean
     */
    public function isLoggedIn()
    {
        return $this->getSession()->has('user');
    }

    /**
     *
     * @return void
     */
    public function logout() 
    {
        if ($this->isLoggedIn()) {
            $this->getSession()->remove('user');
            return true;
        }
        return false;
    }

    /**
     *
     * @return User
     */
    public function getUser() 
    {
        if ($this->isLoggedIn()) {
            $id = $this->getSession()->get('user');
            return User::find($id);
        }
        return false;
    }
}