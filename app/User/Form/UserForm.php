<?php
// app/User/Form/UserForm.php
namespace User\Form;

use Core\Forms\FormBuilder;

class UserForm
{
    public static function build($id = null)
    {
        $userForm = FormBuilder::create('user_form', [
            'action' => '/user',
            'method' => 'POST'
        ])
        ->text('name', [
            'label'         => 'Username',
            'required'      => true,
            'placeholder'   => 'johndoe',
            'class' => 'form-control'
        ])
        ->email('email', [
            'label'         => 'Email',
            'required'      => true,
            'class' => 'form-control'
        ])
        ->text('custom_id', [
            'label'         => 'Custom ID',
            'required'      => true,
            'class' => 'form-control'
        ])
        ->password('password', [
            'label'         => 'Password',
            'required'      => true,
            'class' => 'form-control'
        ])
        #->csrf()
        ;
        if ($id) {
            $userForm->hidden('user_id', $id);
        }
        return $userForm->build();
    }

}