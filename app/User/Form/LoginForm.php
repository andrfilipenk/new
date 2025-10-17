<?php
// app/User/Form/LoginForm.php
namespace User\Form;

use Core\Forms\FormBuilder;

class LoginForm
{
    public static function build()
    {
        $userForm = FormBuilder::create('login_form', [
            'action' => '/login',
            'method' => 'POST'
        ])
        ->text('custom_id', [
            'label'         => 'Custom ID',
            'required'      => true,
            'class' => 'enterprise-input'
        ])
        ->password('password', [
            'label'         => 'Password',
            'required'      => true,
            'class' => 'enterprise-input'
        ])
        #->csrf()
        ;
        return $userForm->build();
    }

}