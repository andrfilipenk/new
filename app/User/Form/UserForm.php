<?php
// app/User/Form/UserForm.php
namespace User\Form;

use Core\Forms\Builder;

class UserForm
{
    public static function build(array $values = []): \Core\Forms\FormInterface
    {
        $builder = new Builder();
        $builder
            ->addText('name', 'Name', [
                'required' => true,
                'rules' => ['required', ['min_length', 2]]
            ])
            ->addEmail('email', 'Email', [
                'required' => true,
                'rules' => ['required', 'email']
            ])
            ->addText('custom_id', 'Custom ID', [
                'required' => true,
                'min' => 0,
                'max' => 9999,
                'rules' => ['required', 'numeric']
            ])
            ->addPassword('password', 'Password', [
                'required' => true,
                'rules' => ['required', ['min_length', 6]]
            ])
            ->addButton('submit', 'Save', ['type' => 'submit', 'class' => 'btn btn-primary']);

        if (!empty($values)) {
            $builder->setValues($values);
        }

        return $builder->build();
    }
}