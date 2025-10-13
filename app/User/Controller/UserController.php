<?php
// app/User/Controller/User.php
namespace User\Controller;

use Core\Forms\FormManager;
use User\Form\UserForm;
use User\Model\User as UserModel;
use User\Model\Groups as GroupModel;

class UserController extends AbstractController
{

    // List all users with their groups
    public function indexAction()
    {
        $users = UserModel::with(['groups'])->get();
        return $this->render('user/user/listing', ['users' => $users]);
    }

    // Show user details with groups
    public function viewAction()
    {
        $user = $this->userByGetID();
        $allGroups = GroupModel::all();
        $userGroups = $user->groups;
        return $this->render('user/user/view', [
            'user' => $user,
            'userGroups' => $userGroups,
            'allGroups' => $allGroups
        ]);
    }

    // Manage user groups (add/remove)
    public function groupsAction()
    {
        $user = $this->userByGetID();
        if (!$user) {
            $this->flashError('User not found.');
            return $this->redirect('user');
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
            return $this->redirect('user/view/' . $user->id);
        }
        return $this->redirect('user/view/' . $user->id);
    }

    // Show create form and handle creation
    public function createAction()
    {
        $formManager = new FormManager(UserForm::build());
        if ($this->isPost()) {
            if ($formManager->handleRequest($this->getRequest()->post())->isValid()) {
                $user = new UserModel($formManager->getValidatedData());
                if ($user->save()) {
                    $this->flashSuccess('User created.');
                    return $this->redirect('user');
                } else {
                    $this->flashError('Failed to create user.');
                }
            }
        }
        return $this->render('main/form', ['form' => $formManager->render()]);
    }

    // Show edit form and handle update
    public function editAction()
    {
        $user = $this->userByGetID();
        $formManager = new FormManager(UserForm::build($user->id));
        $formManager->populateFields($user->getData());
        if ($this->isPost()) {
            if ($formManager->handleRequest($this->getRequest()->post())->isValid()) {
                $user->fill($formManager->getValidatedData());
                if ($user->save()) {
                    $this->flashSuccess('User updated.');
                    return $this->redirect('user');
                } else {
                    $this->flashError('Failed to update user.');
                }
            }
        }
        return $this->render('main/form', ['form' => $formManager->render()]);
    }

    // Delete user
    public function deleteAction()
    {
        $user = $this->userByGetID();
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
        return $this->redirect('user');
    }
}