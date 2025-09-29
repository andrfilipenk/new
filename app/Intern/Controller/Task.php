<?php
namespace Intern\Controller;

use Core\Mvc\Controller;
use Intern\Model\Task as TaskModel;
use Intern\Form\TaskForm;

class Task extends Controller
{
    protected $_statusLabels = [
        TaskModel::STATUS_OPEN      => 'Open',
        TaskModel::STATUS_PROGRESS  => 'Progress',
        TaskModel::STATUS_DONE      => 'Done'
    ];

    
    public function indexAction()
    {
        $tasks = TaskModel::with(['creator', 'assigned'])->get();
        return $this->render('task/tasklist', ['tasks' => $tasks]);
    }

    public function createAction()
    {
        $form = TaskForm::build();

        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            $task = new TaskModel($data);
            if ($task->save()) {
                $this->flashSuccess('Task created.');
                return $this->redirect('admin/tasks');
            } else {
                $this->flashError('Failed to create task.');
            }
            $form->setValues($data);
        }

        return $this->render('task/form', [
            'form' => $form->render()
        ]);
    }

    public function editAction()
    {
        $id = $this->getDI()->get('dispatcher')->getParam('id');
        $task = TaskModel::find($id);
        if ($task === null) {
            $this->flashError('Task not found');
            return $this->redirect('admin/tasks');
        }
        $form = TaskForm::build($task->getData());
        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            $task->fill($data);
            if ($task->save()) {
                $this->flashSuccess('Task updated.');
                return $this->redirect('admin/tasks');
            } else {
                $this->flashError('Failed to update task.');
            }
            $form->setValues($data);
        }

        return $this->render('task/form', [
            'form' => $form->render(),
            'task' => $task
        ]);
    }

    public function deleteAction()
    {
        $id = $this->getDI()->get('dispatcher')->getParam('id');
        $task = TaskModel::find($id);
        if ($task) { // && $task->delete()
            $this->flashSuccess('Task deleted.');
        } else {
            $this->flashError('Failed to delete task.');
        }
        return $this->redirect('admin/tasks');
    }
}