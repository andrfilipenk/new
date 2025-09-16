<?php
// app/Module/Admin/Controller/User.php
namespace Module\Admin\Controller;

use Core\Mvc\Controller;
use Module\Admin\Models\Users;

class User extends Controller
{
    /**
     * returns user
     *
     * @return Users
     */
    protected function getUser()
    {
        /** @var \Core\Mvc\Dispatcher $dispatcher */
        $dispatcher = $this->getDI()->get('dispatcher');
        $id = $dispatcher->getParam('id');
        $user = Users::find($id);
        return $user;
    }

    public function getAction()
    {
        return $this->getUser()->getData();
    }

    public function createAction()
    {
        if ($this->isPost()) {
            
        }
        // returns rendered form
    }

    public function editAction()
    {
        if ($this->isPost()) {

        }
        // returns rendered form
    }
}