<?php
// app/Intern/Controller/Task.php
namespace Intern\Controller;

use Core\Mvc\Controller;
use Intern\Model\Task as TaskModel;
use Intern\Model\TaskStatus;
use Intern\Model\TaskPriority;

class Task extends Controller
{
    /**
     * @return []
     */
    public function getData()
    {
        $request = $this->getRequest();
        $relations = ['creator', 'assigned', 'status', 'priority'];
        $tasks = TaskModel::with($relations)
            ->orderBy('id','desc')
            ->withFilters(function($query) use ($request) {
                if ($status = $request->get('status', null)) {
                    $query->where('status_id', $status);
                }
                if ($priority = $request->get('priority', null)) {
                    $query->where('priority_id', $priority);
                }
                // assigned to 
            })->get();
        return [
            'statuses' => $this->getOptions(TaskStatus::class, 'status'),
            'priorities' => $this->getOptions(TaskPriority::class, 'priority'),
            'tasks' => $tasks
        ];
    }

    public function getOptions($model, $key)
    {
        /** @var \Core\Utils\Url $helper */
        $helper = $this->getDI()->get('url');
        $id = (int)$this->getRequest()->get($key);
        $rows = [];
        foreach ($model::all() as $row) {
            $url = $helper->get(url: 'tasklist', params: [$key => $row->id]);
            if ($row->id === $id) {
                $url = $helper->get('tasklist', [$key => null]);
                $row->active = true;
            }
            $row->url = $url;
            $rows[] = $row;
        }
        return $rows;
    }
    
    public function listAction()
    {
        return $this->render('task/task-list', $this->getData());   
    }

    public function boardAction()
    {
        return $this->render('task/task-board', $this->getData());
    }
}