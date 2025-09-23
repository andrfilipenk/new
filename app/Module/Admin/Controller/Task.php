<?php
namespace Module\Admin\Controller;

use Core\Mvc\Controller;
use Module\Admin\Models\Task as TaskModel;
use Module\Admin\Forms\TaskForm;

class Task extends Controller
{
    public function indexAction()
    {
        $tasks = TaskModel::with(['creator', 'assigned'])->get();
        return $this->render('task/index', ['tasks' => $tasks]);
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
            $response = $this->redirect('admin/tasks');
            var_dump($response);
            return $response;
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