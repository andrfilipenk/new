<?php
// app/Admin/Controller/User.php
namespace Admin\Controller;

use Core\Mvc\Controller;
use Admin\Model\User as UserModel;
use Admin\Model\Groups as GroupModel;
use Admin\Form\UserForm;

class User extends Controller
{
    // List all users with their groups
    public function indexAction()
    {
        $users = UserModel::with(['groups'])->get();
        return $this->render('user/index', ['users' => $users]);
    }

    // Show user details with groups
    public function viewAction()
    {
        $id = $this->getDispatcher()->getParam('id');
        $user = UserModel::find($id);
        if (!$user) {
            $this->flashError('User not found.');
            return $this->redirect('admin/user');
        }
        $allGroups = GroupModel::all();
        $userGroups = $user->groups;
        return $this->render('user/view', [
            'user' => $user,
            'userGroups' => $userGroups,
            'allGroups' => $allGroups
        ]);
    }

    // Manage user groups (add/remove)
    public function groupsAction()
    {
        $id = $this->getDispatcher()->getParam('id');
        $user = UserModel::find($id);
        if (!$user) {
            $this->flashError('User not found.');
            return $this->redirect('admin/user');
        }
        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            $action = $data['action'] ?? '';
            $groupId = (int)($data['group_id'] ?? 0);
            if ($groupId > 0) {
                if ($action === 'add') {
                    $user->addToGroup($groupId);
                    $this->flashSuccess('User added to group.');
                } elseif ($action === 'remove') {
                    $user->removeFromGroup($groupId);
                    $this->flashSuccess('User removed from group.');
                }
            }
            return $this->redirect('admin/user/view/' . $id);
        }
        return $this->redirect('admin/user/view/' . $id);
    }

    // Show create form and handle creation
    public function createAction()
    {
        $form = UserForm::build();
        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            $user = new UserModel($data);
            if ($user->save()) {
                $this->flashSuccess('User created.');
                return $this->redirect('admin/user');
            } else {
                $this->flashError('Failed to create user.');
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
        $id = $this->getDispatcher()->getParam('id');
        $user = UserModel::find($id);
        if (!$user) {
            return $this->redirect('admin/user');
        }
        $form = UserForm::build($user->getData());
        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            $user->fill($data);
            if ($user->save()) {
                $this->flashSuccess('User updated.');
                return $this->redirect('admin/user');
            } else {
                $this->flashError('Failed to update user.');
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
        $id = $this->getDispatcher()->getParam('id');
        $user = UserModel::find($id);
        if ($user) {
            // Remove from all groups first
            $user->groups()->detach();
            if ($user->delete()) {
                $this->flashSuccess('User deleted.');
            } else {
                $this->flashError('Failed to delete user.');
            }
        } else {
            $this->flashError('User not found.');
        }
        return $this->redirect('admin/user');
    }
}