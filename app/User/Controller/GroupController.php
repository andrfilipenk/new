<?php
// app/User/Controller/GroupController.php
namespace User\Controller;

use Core\Forms\FormManager;
use User\Form\GroupForm;
use User\Model\Groups as GroupModel;

class GroupController extends AbstractController
{
    // List all groups
    public function indexAction()
    {
        $groups = GroupModel::with(['users'])->get();
        return $this->render('user/user/groups', ['groups' => $groups]);
    }

    // Create new group
    public function createAction()
    {
        $action = $this->url('group/create');
        $formManager = new FormManager(GroupForm::build()->setAction($action));
        if ($this->isPost()) {
            $formManager->handleRequest($this->getRequest()->post());
            if ($formManager->isSubmitted() && $formManager->isValid()) {
                $group = new GroupModel($formManager->getValidatedData());
                if ($group->save()) {
                    $this->flashSuccess('Group created successfully.');
                    return $this->redirect('group');
                } else {
                    $this->flashError('Failed to create group.');
                }
            }
        }
        return $this->render('user/user/group-form', ['form' => $formManager->render()]);
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
        return $this->redirect('group');
    }
}