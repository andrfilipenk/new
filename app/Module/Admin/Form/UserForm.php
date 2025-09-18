<?php // app/Module/Admin/Form/UserForm.php

namespace Module\Admin\Form;

use Core\Forms\Builder;
use Core\Forms\FormInterface;
use Core\Utils\Tag;

/**
 * UserForm class for admin module.
 * Uses Builder methods for buttons, supports create/update modes.
 * PHP 8.3+ with typed properties and readonly.
 */
class UserForm
{
    private Builder $builder;
    private string $mode = 'create';
    private ?int $id = null;

    public function __construct()
    {
        $this->builder = new Builder();
        $this->builder->addText('name', 'Name', ['maxlength' => 32, 'required' => true]);
        $this->builder->addNumber('kuhnle_id', 'Kuhnle ID', ['min' => 0, 'max' => 9999, 'required' => true]);
        $this->create();
    }

    /**
     * Switch to update mode: use Update button, add hidden user_id.
     * @param int $id Entity ID for hidden field.
     * @param array $data Optional data to populate form.
     * @return self Chainable.
     */
    public function update(int $id, array $data = []): self
    {
        $this->mode = 'update';
        $this->id = $id;
        $this->builder->addHidden('user_id', null, ['value' => $id]);
        $this->builder->addButton('submit', 'Update', ['type' => 'submit']);
        $this->builder->setValues($data);
        return $this;
    }

    /**
     * Set create mode: use Create button.
     * @param array $data Optional data to populate form.
     * @return self Chainable.
     */
    public function create(array $data = []): self
    {
        $this->mode = 'create';
        $this->id = null;
        $this->builder->addButton('submit', 'Create', ['type' => 'submit']);
        $this->builder->setValues($data);
        return $this;
    }

    public function getForm(): FormInterface
    {
        return $this->builder->build();
    }

    public function render(): string
    {
        return $this->builder->render();
    }
}