<?php
// app/User/Form/PermissionForm.php
namespace User\Form;

use Core\Forms\FormBuilder;

class PermissionForm
{
    public static function build($id = null)
    {
        $form = FormBuilder::create('permission_form', [
            'action' => '/permission',
            'method' => 'POST'
        ])
        ->text('name', [
            'label'         => 'Name',
            'required'      => true,
            'placeholder'   => 'Unique Name',
            'maxlength'     => 50,
            'class'         => 'form-control'
        ])
        ->text('display_name', [
            'label'         => 'Display Name',
            'required'      => true,
            'placeholder'   => 'Display Name',
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
        ->text('module', [
            'label'         => 'Module',
            'required'      => true,
            'placeholder'   => 'Module',
            'maxlength'     => 16,
            'class'         => 'form-control'
        ])
        ->text('controller', [
            'label'         => 'Controller',
            'required'      => true,
            'placeholder'   => 'Controller',
            'maxlength'     => 16,
            'class'         => 'form-control'
        ])
        ->text('action', config: [
            'label'         => 'Action',
            'required'      => true,
            'placeholder'   => 'Action',
            'maxlength'     => 16,
            'class'         => 'form-control'
        ])
        #->csrf()
        ;
        if ($id) {
            $form->hidden('id', $id);
        }
        return $form->build();
    }

}