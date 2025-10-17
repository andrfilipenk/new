<?php
// app/Intern/Form/TaskForm.php
namespace Intern\Form;

  /*
        - 
        - begin_date
        - end_date
        - status_id
        - priority_id
        */


use Core\Forms\FormBuilder;

class TaskForm
{
    public static function build($short = false)
    {
        $userForm = FormBuilder::create('user_form', [
            'action' => '/user',
            'method' => 'POST'
        ]);

        $userForm->text('title', [
            'label'         => 'Title',
            'required'      => true,
            'class'         => 'form-control'
        ]);

        $userForm->date('begin_date', [
            'label'         => 'Title',
            'required'      => true,
            'class'         => 'form-control'
        ]);

        $userForm->date('end_date', [
            'label'         => 'Title',
            'required'      => true,
            'class'         => 'form-control'
        ]);

        $userForm->select('priority_id', [], [
            'label'         => 'Priority',
            'required'      => true,
            'class'         => 'form-control'
        ]);

        if (!$short) {
            
        }

        $userForm->select('status_id', [], [
            'label'         => 'Status',
            'required'      => true,
            'class'         => 'form-control'
        ]);

        $userForm->select('created_by', [], [
            'label'         => 'Created by',
            'required'      => true,
            'class'         => 'form-control'
        ]);

        $userForm->select('assigned_to', [], [
            'label'         => 'Assuigned to',
            'class'         => 'form-control'
        ]);
        
        return $userForm->build();
    }

}