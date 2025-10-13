<?php
// app/Core/Forms/Form.php
namespace Core\Forms;

use Core\Utils\Tag;

class Form implements FormInterface
{
    protected array $fields = [];
    protected array $values = [];
    protected array $errors = [];
    protected array $validationRules = [];
    protected array $renderers;
    protected $action;
    protected bool $csrfProtection = true;
    protected string $csrfFieldName = '_token';
    protected string $template = '<form method="post" action="{action}">{fields}</form>';
    protected string $fieldTemplate = '<div class="form-group{error_class}">{label}{field}{error}</div>';

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
            'button'    => fn($n, $f, $v) => $this->buttonRenderer($n, $f, $v),
            'hidden'    => fn($n, $f, $v) => $this->inputRenderer('hidden')($n, $f, $v),
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
        
        // Store validation rules if provided
        if (isset($options['rules'])) {
            $this->validationRules[$name] = $options['rules'];
        }
        
        return $this;
    }

    public function addFieldRenderer(string $type, callable $renderer): static
    {
        $this->renderers[$type] = $renderer;
        return $this;
    }

    public function render(): string
    {
        $output = $this->template;
        
        // Add CSRF token if enabled
        if ($this->csrfProtection) {
            $csrfField = $this->renderCsrfField();
            // Insert CSRF field after opening form tag
            $output = preg_replace('/(<form[^>]*>)/', '$1' . $csrfField, $output);
        }
        
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
        $output = str_replace('{action}', $this->action, $output);
        return str_replace('{fields}', $remainingFields, $output);
    }

    public function renderField(string $name): string
    {
        $field = $this->fields[$name] ?? null;
        if (!$field) return '';
        $value      = $this->values[$name] ?? null;
        $renderer   = $field['renderer'] ?? $this->renderers[$field['type']] ?? $this->renderers['text'];
        $fieldHtml  = $renderer($name, $field, $value);
        
        if ($field['type'] === 'button' || $field['type'] === 'hidden') {
            return $fieldHtml;
        }
        
        // Add error state
        $hasError = $this->hasError($name);
        $errorClass = $hasError ? ' has-error' : '';
        $errorMessages = $hasError ? $this->renderErrors($name) : '';
        
        return str_replace(
            ['{label}', '{field}', '{error}', '{error_class}'],
            [
                Tag::label($field['label'], ['for' => $name]),
                $fieldHtml,
                $errorMessages,
                $errorClass
            ],
            $this->fieldTemplate
        );
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

    private function attrs(string $name, array $field): array
    {
        $attrs = $field['attributes'] ?? [];
        if (!isset($attrs['id'])) {
            $attrs['id'] = $name;
        }
        if (!empty($attrs['required']) || !empty($field['required'])) {
            $attrs['required'] = 'required';
        }
        if ($field['type'] !== 'button') {
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

    public function setAction($action): static
    {
        $this->action = $action;
        return $this;
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
    
    public function setErrors(array $errors): static
    {
        $this->errors = $errors;
        return $this;
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]) && !empty($this->errors[$field]);
    }
    
    public function getValidationRules(): array
    {
        return $this->validationRules;
    }
    
    public function enableCsrfProtection(bool $enable = true): static
    {
        $this->csrfProtection = $enable;
        return $this;
    }
    
    protected function renderErrors(string $name): string
    {
        if (!isset($this->errors[$name])) return '';
        
        $errors = (array) $this->errors[$name];
        $html = '<div class="field-errors">';
        foreach ($errors as $error) {
            $html .= '<span class="error-message text-danger">' . htmlspecialchars($error) . '</span>';
        }
        $html .= '</div>';
        return $html;
    }
    
    protected function renderCsrfField(): string
    {
        $token = $this->getCsrfToken();
        return Tag::input([
            'type' => 'hidden',
            'name' => $this->csrfFieldName,
            'value' => $token
        ]);
    }
    
    protected function getCsrfToken(): string
    {
        // Try to get session from DI container
        try {
            if (class_exists('\Core\Di\Container')) {
                $container = \Core\Di\Container::getInstance();
                if ($container->has('session')) {
                    $session = $container->get('session');
                    
                    if (!$token = $session->get('csrf_token')) {
                        $token = bin2hex(random_bytes(32));
                        $session->set('csrf_token', $token);
                    }
                    
                    return $token;
                }
            }
        } catch (\Exception $e) {
            // Fallback if DI not available
        }
        
        // Fallback: use PHP session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
}