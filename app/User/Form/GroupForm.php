<?php
// app/User/Form/GroupForm.php
namespace User\Form;

use Core\Forms\FormBuilder;

class GroupForm
{
    public static function build($id = null)
    {
        $form = FormBuilder::create('group_form', [
            'action' => '/group',
            'method' => 'POST'
        ])
        ->text('name', [
            'label'         => 'Group Name',
            'required'      => true,
            'placeholder'   => 'Name of Group',
            'maxlength'     => 32,
            'class'         => 'form-control'
        ])
        ->text('code', [
            'label'         => 'Unique group code',
            'required'      => true,
            'placeholder'   => 'Unique group code',
            'maxlength'     => 64,
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