<?php
namespace Intern\Decorator;

use Core\Utils\Dates;
use Core\Utils\Tag;
use Core\Validation\Rules\Date;
use Intern\Model\Task;

class TaskItem {

    /**
     * Holds model
     *
     * @var Task
     */
    protected $_task;

    public function __construct(Task $task)
    {
        $this->_task = $task;
    }

    public function get($key)
    {
        return $this->_task->$key;
    }

    public function getAssignee() {
        return $this->_task->assigned->name;
    }

    public function getStatusBadge()
    {
        $status = $this->_task->status;
        echo Tag::span($status->title, [
            'class' => 'badge text-bg-' . $status->color . ' status-' . $status->code
        ]);
    }

    public function getPriorityBadge()
    {
        $priority = $this->_task->priority;
        echo Tag::span($priority->title, [
            'class' => 'badge text-bg-' . $priority->color . ' priority-' . $priority->code
        ]);
    }

    public function getDatePeriod()
    {
        $class = 'default';
        $today = Dates::today();
        $code  = $this->_task->status->code;
        $end   = Dates::createFromString($this->get('end_date'));
        if (in_array($code, [''])) {
            
        }
        if ($today >= $end) {
            // compute diff
        }
        return Tag::span($end->format("d.m.Y"), ['class' => 'text-' . $class]);
    }
}