<?php
// app/User/Controller/AuthController.php
namespace User\Controller;

use Core\Forms\FormManager;
use User\Form\LoginForm;
use User\Model\User as UserModel;

class AuthController extends AbstractController
{
    public function beforeExecute()
    {
         ;
        return parent::beforeExecute();
    }

    public function loginAction()
    {
        $errors = [];
        $loginAction = $this->url('login');
        $formManager = new FormManager(LoginForm::build()->setAction($loginAction));
        if ($this->isPost()) {
            $formManager->handleRequest($this->getRequest()->post());
            if ($formManager->isSubmitted() && $formManager->isValid()) {
                $data = $formManager->getValidatedData();
                $user = UserModel::byCustomID($data['custom_id']);
                if ($user && $user->verifyPassword($data['password'])) {
                    $this->getSession()->set('user', $user->id);
                    $this->flashSuccess(message: 'Logged in successfully.');
                    return $this->redirect('board');
                }
                $errors[] = 'Invalid credentials.';
            }
        }

        $view = $this->getView();
        $view->disableLayout();
        return $view->render('user/auth/login', [
            'form'  => $formManager->render(),
            'errors' => $formManager->getErrors()
        ]);
    }
}