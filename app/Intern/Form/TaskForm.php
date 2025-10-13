<?php
// app/Intern/Form/TaskForm.php
namespace Intern\Form;

use Core\Forms\Builder;

class TaskForm
{
    /**
     * Build task form with user options
     * 
     * @param array $values Form values to populate
     * @param array $options Additional options like 'users' array
     * @return \Core\Forms\FormInterface
     */
    public static function build(array $values = [], array $options = []): \Core\Forms\FormInterface
    {
        $builder = new Builder();

        // Get user options from controller (no database queries in form!)
        $userOptions = $options['users'] ?? [];
        $statusOptions = $options['statuses'] ?? [
            'open' => 'Open',
            'in-progress' => 'In Progress',
            'done' => 'Done'
        ];

        $builder
            ->setAction('')
            ->addSelect('created_by', $userOptions, 'Created By', [
                'required' => true,
                'rules' => ['required', 'numeric']
            ])
            ->addSelect('assigned_to', $userOptions, 'Assigned To', [
                'required' => true,
                'rules' => ['required', 'numeric']
            ])
            ->addText('title', 'Task Title', [
                'required' => true,
                'rules' => ['required', ['min_length', 3]]
            ])
            ->addDate('begin_date', 'Begin Date', [
                'rules' => ['date']
            ])
            ->addDate('end_date', 'End Date', [
                'rules' => ['date']
            ])
            ->addSelect('status', $statusOptions, 'Status', [
                'required' => true,
                'rules' => ['required']
            ])
            ->addNumber('priority', 'Priority', [
                'required' => true,
                'min' => 1,
                'max' => 10,
                'rules' => ['required', 'numeric', ['min', 1], ['max', 10]]
            ])
            ->addButton('submit', 'Save', ['type' => 'submit', 'class' => 'btn btn-primary']);

        if (!empty($values)) {
            $builder->setValues($values);
        }

        return $builder->build();
    }
}