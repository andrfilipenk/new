<?php
// app/Intern/Controller/TaskController.php
namespace Intern\Controller;

use Core\Mvc\Controller;
use Intern\Model\Task as TaskModel;
use Intern\Model\TaskStatus;
use Intern\Model\TaskPriority;

class TaskController extends Controller
{
    public function addAction()
    {
        $request = $this->getRequest();
        if ($this->isPost() && $this->isAjax()) {
            $item = $request->post();
            $item['created_by'] = $this->getSession()->get('user');
            $task = new TaskModel($item);
            if ($task->save()) {

            }


            return $this->getResponse()->json([
                'status'    => 'success',
                #'redirect'  => $this->url('board'),
                'message'   => 'Vielen Dank',
                'item'      => $item
            ]);
        }
        return $this->getResponse()->json([
            'status' => 'error',
            'message' => 'Error on task create'
        ]);
    }







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
                if ($priority = $request->get(key: 'priority')) {
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
  
}