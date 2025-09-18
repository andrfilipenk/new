<?php
// app/Core/Forms/Form.php
namespace Core\Forms;

use Core\Utils\Tag;

class Form implements FormInterface
{
    protected array $fields = [];
    protected array $values = [];
    protected array $renderers;
    protected string $template = '<form method="post">{fields}</form>';
    protected string $fieldTemplate = '<div class="form-group">{label}{field}</div>';

    public function __construct()
    {
        $this->renderers = [
            'text'      => $this->inputRenderer(),
            'email'     => $this->inputRenderer(),
            'password'  => $this->inputRenderer(), 
            'number'    => $this->inputRenderer(),
            'date'      => $this->inputRenderer(),
            'time'      => $this->inputRenderer(),
            'datetime'  => $this->inputRenderer('datetime-local'),
            'textarea'  => fn($n, $f, $v) => Tag::textarea($v ?: '', $this->attrs($n, $f)),
            'select'    => fn($n, $f, $v) => $this->selectRenderer($n, $f, $v),
            'checkbox'  => fn($n, $f, $v) => $this->checkboxRenderer($n, $f, $v),
            'radio'     => fn($n, $f, $v) => $this->radioRenderer($n, $f, $v),
            'button' => fn($n, $f, $v) => $this->buttonRenderer($n, $f, $v),
            'hidden' => fn($n, $f, $v) => $this->inputRenderer('hidden')($n, $f, $v),
        ];
    }

    private function inputRenderer(?string $typeOverride = null): \Closure
    {
        return fn($name, $field, $value) => Tag::input(array_merge(
            $this->attrs($name, $field),
            [
                'type'  => $typeOverride ?? $field['type'],
                'value' => $this->formatValue($value, $field['type'])
            ]
        ));
    }

    private function buttonRenderer(string $name, array $field, $value): string
    {
        return Tag::button($field['label'], array_merge(
            $this->attrs($name, $field),
            ['type' => $field['attributes']['type'] ?? 'button']
        ));
    }

    public function addField(string $name, string $type, array $options = []): static
    {
        $this->fields[$name] = [
            'type'          => $type,
            'label'         => $options['label'] ?? $this->generateLabel($name),
            'required'      => $options['attributes']['required'] ?? false,
            'attributes'    => $options['attributes'] ?? [],
            'options'       => $options['options'] ?? [],
            'renderer'      => $options['renderer'] ?? null,
        ];
        return $this;
    }

    public function addFieldRenderer(string $type, callable $renderer): static
    {
        $this->renderers[$type] = $renderer;
        return $this;
    }

    public function setValues(array $values): static
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
        $output = $this->template;
        
        // Replace individual field placeholders first
        foreach (array_keys($this->fields) as $name) {
            $placeholder = '{field_' . $name . '}';
            if (strpos($output, $placeholder) !== false) {
                $output = str_replace($placeholder, $this->renderField($name), $output);
            }
        }
        
        // Then replace the general {fields} placeholder with any remaining fields
        $remainingFields = '';
        foreach (array_keys($this->fields) as $name) {
            $placeholder = '{field_' . $name . '}';
            if (strpos($output, $placeholder) === false) {
                $remainingFields .= $this->renderField($name);
            }
        }
        
        return str_replace('{fields}', $remainingFields, $output);
    }

    public function renderField(string $name): string
    {
        $field = $this->fields[$name] ?? null;
        if (!$field) return '';
        $value      = $this->values[$name] ?? null;
        $renderer   = $field['renderer'] ?? $this->renderers[$field['type']] ?? $this->renderers['text'];
        $fieldHtml = $renderer($name, $field, $value);
        if ($field['type'] === 'button' || $field['type'] === 'hidden') {
            return $fieldHtml;
        }
        return str_replace(
            ['{label}', '{field}'],
            [Tag::label($field['label'], ['for' => $name]), $fieldHtml],
            $this->fieldTemplate
        );
    }

    private function selectRenderer(string $name, array $field, $value): string
    {
        $options = [];
        foreach ($field['options'] as $optValue => $label) {
            $options[] = Tag::option($label, [
                'value'     => $optValue,
                'selected'  => (string)$optValue === (string)$value
            ]);
        }
        return Tag::select($options, $this->attrs($name, $field));
    }

    private function checkboxRenderer(string $name, array $field, $value): string
    {
        return Tag::input(array_merge($this->attrs($name, $field), [
            'type'      => 'checkbox',
            'value'     => '1',
            'checked'   => (bool)$value
        ]));
    }

    private function radioRenderer(string $name, array $field, $value): string
    {
        $radios = [];
        foreach ($field['options'] as $optValue => $label) {
            $id = $name . '_' . $optValue;
            $radios[] = Tag::label([
                Tag::input([
                    'type'      => 'radio',
                    'name'      => $name,
                    'value'     => $optValue,
                    'id'        => $id,
                    'checked'   => (string)$optValue === (string)$value
                ]),
                ' ' . $label
            ], ['for' => $id, 'class' => 'radio-inline']);
        }
        return implode('', $radios);
    }

    private function attrs(string $name, array $field): array
    {
        $attrs = $field['attributes'] ?? [];
        // Set id if not present
        if (!isset($attrs['id'])) {
            $attrs['id'] = $name;
        }
        // Set required if present in attributes or field
        if (!empty($attrs['required']) || !empty($field['required'])) {
            $attrs['required'] = 'required';
        }
        if ($field['type'] !== 'button') {
            // Always set name
            $attrs['name'] = $name;
        }
        return $attrs;
    }

    private function formatValue($value, string $type): string
    {
        if ($value instanceof \DateTimeInterface) {
            return match($type) {
                'datetime'  => $value->format('Y-m-d\TH:i'),
                'date'      => $value->format('Y-m-d'),
                'time'      => $value->format('H:i'),
                default => (string) $value
            };
        }
        return (string) $value;
    }

    private function generateLabel(string $name): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $name));
    }

    public function setTemplate(string $template): static
    {
        $this->template = $template;
        return $this;
    }

    public function setFieldTemplate(string $fieldTemplate): static
    {
        $this->fieldTemplate = $fieldTemplate;
        return $this;
    }
}