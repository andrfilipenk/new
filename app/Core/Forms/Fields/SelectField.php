<?php
namespace Core\Forms\Fields;

use Core\Forms\Validation\ValidationResult;

class SelectField extends AbstractField
{
    /**
     * @var array Field options
     */
    private array $options = [];

    /**
     * @var bool Whether multiple selection is allowed
     */
    private bool $multiple = false;

    /**
     * @var string|null Empty option label
     */
    private ?string $emptyOption = null;

    /**
     * Create a new select field
     * 
     * @param string $name Field name
     * @param array $config Field configuration
     */
    public function __construct(string $name, array $config = [])
    {
        parent::__construct($name, $config);
        
        if (isset($config['options'])) {
            $this->setOptions($config['options']);
        }
        
        if (isset($config['multiple'])) {
            $this->setMultiple($config['multiple']);
        }
        
        if (isset($config['emptyOption'])) {
            $this->emptyOption = $config['emptyOption'];
        }
    }

    /**
     * {@inheritdoc}
     */
    protected static function getDefaultType(): string
    {
        return 'select';
    }

    /**
     * Set field options
     * 
     * @param array $options Options array
     * @return self
     */
    public function setOptions(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Get field options
     * 
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Add a single option
     * 
     * @param string|int $value Option value
     * @param string $label Option label
     * @param string|null $group Option group
     * @return self
     */
    public function addOption($value, string $label, ?string $group = null): self
    {
        if ($group !== null) {
            if (!isset($this->options[$group])) {
                $this->options[$group] = [];
            }
            $this->options[$group][$value] = $label;
        } else {
            $this->options[$value] = $label;
        }
        
        return $this;
    }

    /**
     * Set whether multiple selection is allowed
     * 
     * @param bool $multiple Multiple flag
     * @return self
     */
    public function setMultiple(bool $multiple): self
    {
        $this->multiple = $multiple;
        
        if ($multiple) {
            $this->setAttribute('multiple', 'multiple');
            // Ensure name ends with [] for array submission
            if (!str_ends_with($this->name, '[]')) {
                $this->name .= '[]';
            }
        } else {
            unset($this->attributes['multiple']);
        }
        
        return $this;
    }

    /**
     * Check if multiple selection is allowed
     * 
     * @return bool
     */
    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * Set empty option label
     * 
     * @param string|null $label Empty option label (null to disable)
     * @return self
     */
    public function setEmptyOption(?string $label): self
    {
        $this->emptyOption = $label;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function renderDefault(array $context): string
    {
        $html = [];
        
        // Field wrapper
        $fieldClasses = ['form-field', 'form-field-select'];
        if (isset($context['errors'][$this->name])) {
            $fieldClasses[] = 'has-error';
        }
        $html[] = sprintf('<div class="%s">', implode(' ', $fieldClasses));
        
        // Label
        if ($this->label) {
            $requiredMark = $this->required ? ' <span class="required">*</span>' : '';
            $html[] = sprintf(
                '<label for="%s">%s%s</label>',
                $this->getAttribute('id'),
                $this->escape($this->label),
                $requiredMark
            );
        }
        
        // Select element
        $html[] = sprintf('<select %s>', $this->buildAttributesString());
        
        // Empty option
        if ($this->emptyOption !== null) {
            $html[] = sprintf(
                '<option value="">%s</option>',
                $this->escape($this->emptyOption)
            );
        }
        
        // Options
        $html[] = $this->renderOptions($this->options, $this->value);
        
        $html[] = '</select>';
        
        // Help text
        if ($this->helpText && ($context['show_help_text'] ?? true)) {
            $html[] = sprintf(
                '<small class="help-text">%s</small>',
                $this->escape($this->helpText)
            );
        }
        
        // Errors
        if (isset($context['errors'][$this->name]) && ($context['show_errors'] ?? true)) {
            $errors = $context['errors'][$this->name];
            $html[] = '<div class="field-errors">';
            foreach ($errors as $error) {
                $html[] = sprintf('<span class="error">%s</span>', $this->escape($error));
            }
            $html[] = '</div>';
        }
        
        $html[] = '</div>'; // Close field wrapper
        
        return implode("\n", $html);
    }

    /**
     * Render options recursively (supports optgroups)
     * 
     * @param array $options Options to render
     * @param mixed $selectedValue Selected value(s)
     * @return string
     */
    private function renderOptions(array $options, mixed $selectedValue): string
    {
        $html = [];
        $selectedValues = $this->normalizeSelectedValues($selectedValue);
        
        foreach ($options as $key => $value) {
            if (is_array($value)) {
                // Optgroup
                $html[] = sprintf('<optgroup label="%s">', $this->escape($key));
                $html[] = $this->renderOptions($value, $selectedValue);
                $html[] = '</optgroup>';
            } else {
                // Regular option
                $selected = in_array((string)$key, $selectedValues, true) ? ' selected' : '';
                $html[] = sprintf(
                    '<option value="%s"%s>%s</option>',
                    $this->escape($key),
                    $selected,
                    $this->escape($value)
                );
            }
        }
        
        return implode("\n", $html);
    }

    /**
     * Normalize selected values to array of strings
     * 
     * @param mixed $value Selected value(s)
     * @return array
     */
    private function normalizeSelectedValues(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        
        if (!is_array($value)) {
            return [(string)$value];
        }
        
        return array_map('strval', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(mixed $value = null): ValidationResult
    {
        $result = parent::validate($value);
        
        if ($result->isFailed()) {
            return $result;
        }
        
        $valueToValidate = $value ?? $this->value;
        
        // Check if value(s) exist in options
        if ($valueToValidate !== null && $valueToValidate !== '') {
            $validOptions = $this->flattenOptions($this->options);
            $valuesToCheck = is_array($valueToValidate) ? $valueToValidate : [$valueToValidate];
            
            foreach ($valuesToCheck as $val) {
                if (!array_key_exists($val, $validOptions)) {
                    $result->addError($this->name, 'Invalid option selected.');
                    break;
                }
            }
        }
        
        return $result;
    }

    /**
     * Flatten options array (handles optgroups)
     * 
     * @param array $options Options array
     * @return array Flattened options
     */
    private function flattenOptions(array $options): array
    {
        $flattened = [];
        
        foreach ($options as $key => $value) {
            if (is_array($value)) {
                $flattened = array_merge($flattened, $this->flattenOptions($value));
            } else {
                $flattened[$key] = $value;
            }
        }
        
        return $flattened;
    }

    /**
     * Create a select field
     * 
     * @param string $name Field name
     * @param array $options Field options
     * @param array $config Additional configuration
     * @return self
     */
    public static function make(string $name, array $options = [], array $config = []): self
    {
        $config['options'] = $options;
        return new self($name, $config);
    }
}
