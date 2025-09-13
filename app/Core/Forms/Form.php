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
        $output = '';
        
        foreach ($this->fields as $name => $field) {
            $output .= $this->renderField($name);
        }
        
        return $this->wrapForm($output);
    }

    public function renderField(string $name): string
    {
        if (!isset($this->fields[$name])) {
            return '';
        }

        $field = $this->fields[$name];
        $value = $this->values[$name] ?? '';
        
        // Try custom field template first
        $fieldTemplate = $this->templatesPath . 'fields/' . $this->fieldTemplate . '.phtml';
        if (file_exists($fieldTemplate)) {
            return $this->renderTemplate($fieldTemplate, [
                'field' => $field,
                'name' => $name,
                'value' => $value
            ]);
        }
        
        // Fall back to built-in rendering
        return $this->renderFieldHtml($name, $field, $value);
    }

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

    protected function renderFieldHtml(string $name, array $field, $value, $required = ''): string
    {
        $type       = $field['type'];
        $options    = $field['options'];
        $attributes = $this->buildAttributes($options['attributes']);
        $label      = $options['label'];
        $required   = $options['required'] ? ' required' : '';
        
        switch ($type) {
            case 'text':
            case 'email':
            case 'password':
            case 'number':
            case 'datetime':
            case 'date':
            case 'time':
                $type  = $type === 'datetime' ? 'datetime-local' : $type;
                $value = $type === 'datetime'   && $value instanceof \DateTimeInterface ? $value->format('Y-m-d\TH:i') : $value;
                $value = $type === 'date'       && $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;
                $value = $type === 'time'       && $value instanceof \DateTimeInterface ? $value->format('H:i') : $value;
                return <<<HTML
<div class="form-group">
    <label for="$name">$label</label>
    <input type="$type" name="$name" value="$value"$attributes$required>
</div>
HTML;

            case 'textarea':
                return <<<HTML
<div class="form-group">
    <label for="$name">$label</label>
    <textarea name="$name"$attributes$required>$value</textarea>
</div>
HTML;

            case 'select':
                $selectOptions = '';
                foreach ($options['options'] as $optValue => $optLabel) {
                    $selected = $optValue == $value ? ' selected' : '';
                    $selectOptions .= "<option value=\"$optValue\"$selected>$optLabel</option>";
                }
                return <<<HTML
<div class="form-group">
    <label for="$name">$label</label>
    <select name="$name"$attributes$required>$selectOptions</select>
</div>
HTML;

            case 'checkbox':
                $checked = $value ? ' checked' : '';
                return <<<HTML
<div class="form-group checkbox">
    <label>
        <input type="checkbox" name="$name" value="1"$attributes$checked$required> $label
    </label>
</div>
HTML;

            case 'radio':
                $radioOptions = '';
                foreach ($options['options'] as $optValue => $optLabel) {
                    $checked = $optValue == $value ? ' checked' : '';
                    $radioOptions .= <<<HTML
<label class="radio-inline">
    <input type="radio" name="$name" value="$optValue"$checked$required> $optLabel
</label>
HTML;
                }
                return <<<HTML
<div class="form-group">
    <label>$label</label>
    <div>$radioOptions</div>
</div>
HTML;

            default:
                return '';
        }
    }

    protected function buildAttributes(array $attributes): string
    {
        $html = '';
        foreach ($attributes as $key => $value) {
            $html .= " $key=\"$value\"";
        }
        return $html;
    }

    protected function wrapForm(string $content): string
    {
        $formTemplate = $this->templatesPath . $this->template . '.phtml';
        
        if (file_exists($formTemplate)) {
            return $this->renderTemplate($formTemplate, ['content' => $content]);
        }
        
        // Default form wrapper
        return <<<HTML
<form method="post">
    $content
    <div class="form-group">
        <button type="submit">Submit</button>
    </div>
</form>
HTML;
    }

    protected function renderTemplate(string $templatePath, array $data = []): string
    {
        extract($data);
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }

    public function setTemplatesPath(string $path): FormInterface
    {
        $this->templatesPath = rtrim($path, '/') . '/';
        return $this;
    }
}