<?php
// app/_Core/Forms/Builder.php
namespace Core\Forms;

class Builder
{
    private Form $form;
    private static array $typeMap = [
        'text'      => 'text',
        'email'     => 'email',
        'password'  => 'password',
        'number'    => 'number',
        'date'      => 'date',
        'time'      => 'time',
        'datetime'  => 'datetime',
        'textarea'  => 'textarea',
        'select'    => 'select',
        'radio'     => 'radio',
        'checkbox'  => 'checkbox',
        'button'    => 'button', // Added button type
        'hidden'    => 'hidden', // Added hidden type
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
        $fieldName  = $args[0];
        $options    = [];
        // Normalize attributes: merge all options into 'attributes'
        $attributes = [];
        // For select/radio: args[3], for button: args[2], otherwise: args[2]
        if (in_array($type, ['select', 'radio'])) {
            $attributes = $args[3] ?? [];
        } elseif ($type === 'button') {
            $attributes = $args[2] ?? [];
        } else {
            $attributes = $args[2] ?? [];
        }
        // Move standard HTML attributes from options to 'attributes'
        foreach (['required', 'placeholder', 'class', 'id', 'min', 'max', 'step', 'readonly', 'disabled', 'autocomplete', 'pattern'] as $attrKey) {
            if (isset($attributes[$attrKey])) {
                $attributes[$attrKey] = $attributes[$attrKey];
            }
            // Also support if passed as a separate argument (not inside attributes array)
            if (isset($args[2][$attrKey])) {
                $attributes[$attrKey] = $args[2][$attrKey];
            }
        }
        if (in_array($type, ['select', 'radio'])) {
            $options = [
                'options'       => $args[1] ?? [],
                'label'         => $args[2] ?? null,
                'attributes'    => $attributes
            ];
        } elseif ($type === 'button') {
            $options = [
                'label'         => $args[1] ?? null,
                'attributes'    => $attributes,
            ];
        } else {
            $options = [
                'label'         => $args[1] ?? null,
                'attributes'    => $attributes
            ];
        }
        $this->form->addField($fieldName, $type, array_filter($options));
        return $this;
    }

    public function setAction($action): self
    {
        $this->form->setAction($action);
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