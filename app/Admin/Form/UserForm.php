<?php
// app/Admin/Form/UserForm.php
namespace Admin\Form;

use Core\Forms\Builder;

class UserForm
{
    public static function build(array $values = []): \Core\Forms\FormInterface
    {
        $builder = new Builder();
        $builder
            ->addText('name', 'Name', ['required' => true])
            ->addEmail('email', 'Email', ['required' => true])
            ->addText('kuhnle_id', 'Kuhnle ID', ['required' => true, 'min' => 0, 'max' => 9999])
            ->addPassword('password', 'Password', ['required' => true])
            ->addButton('submit', 'Save', ['type' => 'submit', 'class' => 'btn btn-primary']);

        if (!empty($values)) {
            $builder->setValues($values);
        }

        return $builder->build();
    }
}