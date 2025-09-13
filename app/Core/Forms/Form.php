<?php

namespace Core\Forms;

use Core\Utils\Tag;

class Form implements FormInterface
{
    protected $fields = [];
    protected $values = [];
    protected $template = 'default';
    protected $fieldTemplate = 'default';
    protected $templatesPath;

    public function __construct()
    {
        $this->templatesPath = dirname(__DIR__) . '/views/forms/';
    }

    public function addField(string $name, string $type, array $options = []): FormInterface
    {
        $this->fields[$name] = [
            'type' => $type,
            'options' => array_merge([
                'label' => $this->generateLabel($name),
                'required' => false,
                'attributes' => [],
                'options' => [], // For select, radio, checkbox groups
            ], $options)
        ];

        return $this;
    }

    public function setValues(array $values): FormInterface
    {
        $this->values = array_merge($this->values, $values);
        return $this;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function render(): string
    {
        $fieldsHtml = '';
        foreach (array_keys($this->fields) as $name) {
            $fieldsHtml .= $this->renderField($name);
        }
        
        return $this->wrapForm($fieldsHtml);
    }

    public function renderField(string $name): string
    {
        if (!isset($this->fields[$name])) {
            return '';
        }

        $field = $this->fields[$name];
        $value = $this->values[$name] ?? null;
        $label = Tag::label($field['options']['label'], ['for' => $name]);
        $fieldElement = $this->renderFieldElement($name, $field, $value);

        // Example of a simple, default field wrapper using Tag
        return Tag::div([$label, $fieldElement], ['class' => 'form-group']);
    }

    protected function renderFieldElement(string $name, array $field, $value)
    {
        $type = $field['type'];
        $method = 'render' . ucfirst($type) . 'Field';

        if (method_exists($this, $method)) {
            return $this->{$method}($name, $field, $value);
        }

        // Fallback for simple input types
        return $this->renderInputField($name, $field, $value);
    }

    protected function buildAttributes(string $name, array $field, $value): array
    {
        $attributes = $field['options']['attributes'];
        $attributes['name'] = $name;
        $attributes['id'] = $attributes['id'] ?? $name;

        if ($field['options']['required']) {
            $attributes['required'] = true;
        }

        return $attributes;
    }

    protected function renderInputField(string $name, array $field, $value)
    {
        $attributes = $this->buildAttributes($name, $field, $value);
        $type = $field['type'];

        $attributes['type'] = ($type === 'datetime') ? 'datetime-local' : $type;
        
        if ($value instanceof \DateTimeInterface) {
            if ($type === 'datetime') $value = $value->format('Y-m-d\TH:i');
            elseif ($type === 'date') $value = $value->format('Y-m-d');
            elseif ($type === 'time') $value = $value->format('H:i');
        }
        
        $attributes['value'] = $value;

        return Tag::input($attributes);
    }

    protected function renderTextareaField(string $name, array $field, $value)
    {
        $attributes = $this->buildAttributes($name, $field, $value);
        return Tag::textarea((string) $value, $attributes);
    }

    protected function renderSelectField(string $name, array $field, $value)
    {
        $attributes = $this->buildAttributes($name, $field, $value);
        $optionsHtml = [];

        foreach ($field['options']['options'] as $optValue => $optLabel) {
            $optionAttrs = ['value' => $optValue];
            if ((string)$optValue === (string)$value) {
                $optionAttrs['selected'] = true;
            }
            $optionsHtml[] = Tag::option($optLabel, $optionAttrs);
        }

        return Tag::select($optionsHtml, $attributes);
    }

    protected function renderCheckboxField(string $name, array $field, $value)
    {
        $attributes = $this->buildAttributes($name, $field, $value);
        $attributes['type'] = 'checkbox';
        $attributes['value'] = $attributes['value'] ?? '1';

        if ($value) {
            $attributes['checked'] = true;
        }

        return Tag::input($attributes);
    }

    protected function renderRadioField(string $name, array $field, $value)
    {
        $attributes = $this->buildAttributes($name, $field, $value);
        $attributes['type'] = 'radio';
        $radiosHtml = [];

        foreach ($field['options']['options'] as $optValue => $optLabel) {
            $radioAttrs = $attributes;
            $radioAttrs['value'] = $optValue;
            $radioAttrs['id'] .= '_' . $optValue;

            if ((string)$optValue === (string)$value) {
                $radioAttrs['checked'] = true;
            }
            
            $radiosHtml[] = Tag::label([
                Tag::input($radioAttrs),
                ' ' . $optLabel
            ], ['for' => $radioAttrs['id'], 'class' => 'radio-inline']);
        }

        return implode('', $radiosHtml);
    }

    protected function wrapForm(string $content): string
    {
        // Default form wrapper using Tag
        return Tag::form([
            $content,
            Tag::div(
                Tag::button('Submit', ['type' => 'submit']),
                ['class' => 'form-group']
            )
        ], ['method' => 'post']);
    }

    // --- Template methods remain unchanged ---

    public function setTemplate(string $template): FormInterface
    {
        $this->template = $template;
        return $this;
    }

    public function setFieldTemplate(string $fieldTemplate): FormInterface
    {
        $this->fieldTemplate = $fieldTemplate;
        return $this;
    }

    protected function generateLabel(string $name): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $name));
    }

    public function setTemplatesPath(string $path): FormInterface
    {
        $this->templatesPath = rtrim($path, '/') . '/';
        return $this;
    }
}

/* Example usage:
$form = new Form();
$form->addField('username', 'text', ['label' => 'Username', 'required' => true]);
$form->addField('password', 'password', ['label' => 'Password', 'required' => true]);
$form->addField('bio', 'textarea', ['label' => 'Biography']);
$form->addField('country', 'select', [
    'label' => 'Country',
    'options' => ['us' => 'USA', 'ca' => 'Canada'],
    'required' => true
]);
$form->addField('subscribe', 'checkbox', ['label' => 'Subscribe to newsletter']);
$form->setValues(['username' => 'johndoe', 'country' => 'ca', 'subscribe' => true]);
echo $form->render();


*/  