<?php
// app/User/Form/RoleForm.php
namespace User\Form;

use Core\Forms\FormBuilder;

class RoleForm
{
    public static function build($id = null)
    {
        $form = FormBuilder::create('role_form', [
            'action' => '/role',
            'method' => 'POST'
        ])
        ->text('name', [
            'label'         => 'Role Name',
            'required'      => true,
            'placeholder'   => 'Unique Role Name',
            'maxlength'     => 50,
            'class'         => 'form-control'
        ])
        ->text('display_name', [
            'label'         => 'Display Role Name',
            'required'      => true,
            'placeholder'   => 'Display Role Name',
            'maxlength'     => 100,
            'class'         => 'form-control'
        ])
        ->textarea('description', [
            'label'         => 'Description',
            'required'      => true,
            'placeholder'   => 'Description',
            'class'         => 'form-control',
            'rows'          => 3
        ])
        #->csrf()
        ;
        if ($id) {
            $form->hidden('id', $id);
        }
        return $form->build();
    }

}