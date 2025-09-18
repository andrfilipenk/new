<?php
namespace Module\Admin\Forms;

use Core\Forms\Builder;
use Module\Admin\Models\Users;

class TaskForm
{
    public static function build(array $values = []): \Core\Forms\FormInterface
    {
        $builder = new Builder();

        // User options for selects
        $users = Users::all();
        $userOptions = [];
        foreach ($users as $user) {
            $userOptions[$user->user_id] = $user->name . ' (' . $user->email . ')';
        }

        $builder
            ->addSelect('created_by', $userOptions, 'Created By', ['required' => true])
            ->addSelect('assigned_to', $userOptions, 'Assigned To', ['required' => true])
            ->addText('title', 'Task Title', ['required' => true])
            ->addDate('created_date', 'Created Date', ['required' => true])
            ->addDate('begin_date', 'Begin Date')
            ->addDate('end_date', 'End Date')
            ->addSelect('status', [
                'open' => 'Open',
                'in-progress' => 'In Progress',
                'done' => 'Done'
            ], 'Status', ['required' => true])
            ->addNumber('priority', 'Priority', ['required' => true, 'min' => 1, 'max' => 10])
            ->addButton('submit', 'Save', ['type' => 'submit', 'class' => 'btn btn-primary']);

        if (!empty($values)) {
            $builder->setValues($values);
        }

        return $builder->build();
    }
}