<?php

namespace Core\Forms;

class Builder
{
    private Form $form;
    private static array $typeMap = [
        'text' => 'text', 'email' => 'email', 'password' => 'password',
        'number' => 'number', 'date' => 'date', 'time' => 'time', 
        'datetime' => 'datetime', 'textarea' => 'textarea',
        'select' => 'select', 'radio' => 'radio', 'checkbox' => 'checkbox'
    ];

    public function __construct()
    {
        $this->form = new Form();
    }

    public function __call(string $name, array $args): self
    {
        if (!str_starts_with($name, 'add')) {
            throw new \BadMethodCallException("Method {$name} not found");
        }

        $type = strtolower(preg_replace('/^add|Field$|Group$/i', '', $name));
        
        if (!isset(self::$typeMap[$type])) {
            throw new \BadMethodCallException("Unknown field type: {$type}");
        }

        $fieldName = $args[0];
        $options = [];

        if (in_array($type, ['select', 'radio'])) {
            $options = [
                'options' => $args[1] ?? [],
                'label' => $args[2] ?? null,
                'attributes' => $args[3] ?? []
            ];
        } else {
            $options = [
                'label' => $args[1] ?? null,
                'attributes' => $args[2] ?? []
            ];
        }

        $this->form->addField($fieldName, $type, array_filter($options));
        return $this;
    }

    public function setValues(array $values): self
    {
        $this->form->setValues($values);
        return $this;
    }

    public function setTemplate(string $template): self
    {
        $this->form->setTemplate($template);
        return $this;
    }

    public function setFieldTemplate(string $template): self
    {
        $this->form->setFieldTemplate($template);
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

// Example for name, beginDate, endDate, users_id
/*
$userOptions = [1 => 'John Doe', 2 => 'Jane Smith', 3 => 'Mike Johnson'];

$form = (new Builder())
    ->addTextField('name', 'Project Name', ['class' => 'form-control', 'required' => true])
    ->addDateField('beginDate', 'Start Date', ['class' => 'form-control'])
    ->addDateField('endDate', 'End Date', ['class' => 'form-control'])
    ->addSelect('users_id', $userOptions, 'Assigned User', ['class' => 'form-select'])
    ->setValues([
        'name' => 'Project Alpha',
        'beginDate' => '2024-01-15',
        'endDate' => '2024-06-30',
        'users_id' => 2
    ])
    ->setTemplate('<form method="post" class="needs-validation">{fields}<button type="submit" class="btn btn-primary">Save Project</button></form>')
    ->setFieldTemplate('<div class="mb-3">{label}{field}</div>')
    ->render();

echo $form;
*/