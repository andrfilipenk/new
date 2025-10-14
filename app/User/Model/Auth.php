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

    public function isLoggedIn()
    {
        return $this->getSession()->has('user');
    }

    public function getUser() {
        if ($this->isLoggedIn()) {
            $id = $this->getSession()->get('user');
            return User::find($id);
        }
        return false;
    }
}