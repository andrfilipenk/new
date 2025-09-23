<?php
namespace Module\Admin\Controller;

use Core\Mvc\Controller;
use Module\Admin\Models\User as UserModel;
use Module\Admin\Forms\UserForm;

class User extends Controller
{
    // List all users
    public function indexAction()
    {
        $users = UserModel::all();
        return $this->render('user/index', ['users' => $users]);
    }

    // Show create form and handle creation
    public function createAction()
    {
        $form = UserForm::build();

        if ($this->isPost()) {
            $data = $this->getRequest()->all();

            // You should add validation here!
            $user = new UserModel($data);
            if ($user->save()) {
                $this->getDI()->get('session')->flash('success', 'User created.');
                return $this->redirect('admin/users');
            } else {
                $this->getDI()->get('session')->flash('error', 'Failed to create user.');
            }
            $form->setValues($data);
        }

        return $this->render('user/form', [
            'form' => $form->render()
        ]);
    }

    // Show edit form and handle update
    public function editAction()
    {
        $id = $this->getDI()->get('dispatcher')->getParam('id');

        $user = UserModel::find($id);

        if (!$user) {
            return $this->redirect('admin/users');
        }

        $form = UserForm::build($user->getData());

        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            $user->fill($data);
            if ($user->save()) {
                $this->getDI()->get('session')->flash('success', 'User updated.');
                return $this->redirect('admin/users');
            } else {
                $this->getDI()->get('session')->flash('error', 'Failed to update user.');
            }
            $form->setValues($data);
        }

        return $this->render('user/form', [
            'form' => $form->render(),
            'user' => $user
        ]);
    }

    // Delete user
    public function deleteAction()
    {
        $id = $this->getDI()->get('dispatcher')->getParam('id');
        $user = UserModel::find($id);
        if ($user) { // && $user->delete()
            $this->getDI()->get('session')->flash('success', 'User deleted.');
        } else {
            $this->getDI()->get('session')->flash('error', 'Failed to delete user.');
        }
        return $this->redirect('admin/users');
    }
}