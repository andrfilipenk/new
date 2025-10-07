<?php
// app/User/Controller/Group.php
namespace User\Controller;

use Core\Mvc\Controller;
use User\Model\Groups as GroupModel;

class Group extends Controller
{
    // List all groups
    public function indexAction()
    {
        $groups = GroupModel::with(['users'])->get();
        return $this->render('user/groups', ['groups' => $groups]);
    }

    // Create new group
    public function createAction()
    {
        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            $name = trim($data['name'] ?? '');
            $code = trim($data['code'] ?? '');
            if ($name && $code) {
                $group = GroupModel::createGroup($name, $code);
                if ($group) {
                    $this->flashSuccess('Group created successfully.');
                } else {
                    $this->flashError('Failed to create group.');
                }
            } else {
                $this->flashError('Name and code are required.');
            }
            return $this->redirect('user/groups');
        }
        return $this->render('user/create-group');
    }

    // Delete group
    public function deleteAction()
    {
        $id = $this->getDispatcher()->getParam('id');
        /** @var GroupModel $group */
        $group = GroupModel::find($id);
        if ($group) {
            // Remove all user-group associations first
            $group->users()->detach();
            if ($group->delete()) {
                $this->flashSuccess('Group deleted successfully.');
            } else {
                $this->flashError('Failed to delete group.');
            }
        } else {
            $this->flashError('Group not found.');
        }
        return $this->redirect('user/groups');
    }
}