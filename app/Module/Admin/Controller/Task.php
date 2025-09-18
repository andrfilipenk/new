<?php
namespace Module\Admin\Controller;

use Core\Mvc\Controller;
use Module\Admin\Models\Tasks;
use Module\Admin\Forms\TaskForm;

class Task extends Controller
{
    public function indexAction()
    {
        $tasks = Tasks::with(['creator', 'assigned'])->get();
        return $this->render('task/index', ['tasks' => $tasks]);
    }

    public function createAction()
    {
        $form = TaskForm::build();

        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            $task = new Tasks($data);
            if ($task->save()) {
                $this->getDI()->get('session')->flash('success', 'Task created.');
                return $this->redirect('/admin/tasks');
            } else {
                $this->getDI()->get('session')->flash('error', 'Failed to create task.');
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
        $task = Tasks::find($id);
        if (!$task) {
            return $this->redirect('/admin/tasks');
        }
        $form = TaskForm::build($task->getData());

        if ($this->isPost()) {
            $data = $this->getRequest()->all();
            $task->fill($data);
            if ($task->save()) {
                $this->getDI()->get('session')->flash('success', 'Task updated.');
                return $this->redirect('/admin/tasks');
            } else {
                $this->getDI()->get('session')->flash('error', 'Failed to update task.');
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
        $task = Tasks::find($id);
        if ($task) { // && $task->delete()
            $this->getDI()->get('session')->flash('success', 'Task deleted.');
        } else {
            $this->getDI()->get('session')->flash('error', 'Failed to delete task.');
        }
        return $this->redirect('/admin/tasks');
    }
}