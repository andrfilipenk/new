<?php
// app/Intern/Form/TaskForm.php
namespace Intern\Form;

use Core\Forms\Builder;
use Admin\Model\User;

class TaskForm
{
    public static function build(array $values = []): \Core\Forms\FormInterface
    {
        $builder = new Builder();

        // User options for selects
        $users = User::all();
        $userOptions = [];
        foreach ($users as $user) {
            $userOptions[$user->id] = $user->name . ' (' . $user->kuhnle_id . ')';
        }

        $builder
            ->addSelect('created_by', $userOptions, 'Created By', ['required' => true])
            ->addSelect('assigned_to', $userOptions, 'Assigned To', ['required' => true])
            ->addText('title', 'Task Title', ['required' => true])
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