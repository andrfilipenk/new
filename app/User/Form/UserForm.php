<?php
// app/User/Form/UserForm.php
namespace User\Form;

use Core\Forms\FormBuilder;

// name, email, custom_id, password


class UserForm
{
    public static function build($id = null)
    {
        $userForm = FormBuilder::create('user_form', [
            'action' => '/register',
            'method' => 'POST'
        ])
        ->text('name', [
            'label'         => 'Username',
            'required'      => true,
            'placeholder'   => 'johndoe'
        ])
        ->email('email', [
            'label'         => 'Email',
            'required'      => true
        ])
        ->text('custom_id', [
            'label'         => 'Custom ID',
            'required'      => true
        ])
        ->password('password', [
            'label'         => 'Password',
            'required'      => true
        ])
        ->csrf();
        if ($id) {
            $userForm->hidden('user_id', $id);
        }
        return $userForm->build();
    }

}