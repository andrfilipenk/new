<?php
// app/User/Controller/AbstractController.php
namespace User\Controller;

use Core\Mvc\Controller;
use User\Model\User as UserModel;
use User\Model\Groups as GroupModel;

abstract class AbstractController extends Controller {

    /**
     * Undocumented function
     *
     * @return UserModel
     */
    protected function userByGetID()
    {
        $id = $this->getDispatcher()->getParam('id');
        return UserModel::find($id);
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function beforeExecute()
    {
        if (in_array($this->getDispatcher()->getAction(), ['view', 'edit', 'delete'])) {
            $user = $this->userByGetID();
            if (!$user) {
                $this->flashError('User not found.');
                return $this->redirect('user');
            }
        }
        return parent::beforeExecute();
    }

    protected function render(string $template = null, array $data = []): string
    {
        return $this->getView()->render($template, $data);
    }
}