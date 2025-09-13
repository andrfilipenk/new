<?php

namespace Core\Forms;

class Builder
{
    protected $form;

    public function __construct()
    {
        $this->form = new Form();
    }

    public function addTextField(string $name, string $label = null, array $attributes = []): Builder
    {
        $options = ['label' => $label, 'attributes' => $attributes];
        $this->form->addField($name, 'text', $options);
        return $this;
    }

    public function addEmailField(string $name, string $label = null, array $attributes = []): Builder
    {
        $options = ['label' => $label, 'attributes' => $attributes];
        $this->form->addField($name, 'email', $options);
        return $this;
    }

    public function addPasswordField(string $name, string $label = null, array $attributes = []): Builder
    {
        $options = ['label' => $label, 'attributes' => $attributes];
        $this->form->addField($name, 'password', $options);
        return $this;
    }

    public function addTextarea(string $name, string $label = null, array $attributes = []): Builder
    {
        $options = ['label' => $label, 'attributes' => $attributes];
        $this->form->addField($name, 'textarea', $options);
        return $this;
    }

    public function addSelect(string $name, array $options, string $label = null, array $attributes = []): Builder
    {
        $fieldOptions = ['label' => $label, 'attributes' => $attributes, 'options' => $options];
        $this->form->addField($name, 'select', $fieldOptions);
        return $this;
    }

    public function addCheckbox(string $name, string $label = null, array $attributes = []): Builder
    {
        $options = ['label' => $label, 'attributes' => $attributes];
        $this->form->addField($name, 'checkbox', $options);
        return $this;
    }

    public function addRadioGroup(string $name, array $options, string $label = null, array $attributes = []): Builder
    {
        $fieldOptions = ['label' => $label, 'attributes' => $attributes, 'options' => $options];
        $this->form->addField($name, 'radio', $fieldOptions);
        return $this;
    }

    public function addDateTimeField(string $name, string $label = null, array $attributes = []): Builder
    {
        $options = ['label' => $label, 'attributes' => $attributes];
        $this->form->addField($name, 'datetime', $options);
        return $this;
    }

    public function addDateField(string $name, string $label = null, array $attributes = []): Builder
    {
        $options = ['label' => $label, 'attributes' => $attributes];
        $this->form->addField($name, 'date', $options);
        return $this;
    }

    public function addTimeField(string $name, string $label = null, array $attributes = []): Builder
    {
        $options = ['label' => $label, 'attributes' => $attributes];
        $this->form->addField($name, 'time', $options);
        return $this;
    }

    public function setValues(array $values): Builder
    {
        $this->form->setValues($values);
        return $this;
    }

    public function setTemplate(string $template): Builder
    {
        $this->form->setTemplate($template);
        return $this;
    }

    public function setFieldTemplate(string $fieldTemplate): Builder
    {
        $this->form->setFieldTemplate($fieldTemplate);
        return $this;
    }

    public function build(): FormInterface
    {
        return $this->form;
    }

    public function render(): string
    {
        return $this->form->render();
    }
}