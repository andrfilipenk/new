<?php
namespace Core\Forms\Fields;

use Core\Forms\Validation\ValidationResult;

class InputField extends AbstractField
{
    /**
     * @var array Supported input types
     */
    private const SUPPORTED_TYPES = [
        'text', 'email', 'password', 'number', 'tel', 'url',
        'search', 'date', 'time', 'datetime-local', 'month', 'week',
        'color', 'range', 'hidden'
    ];

    /**
     * Create a new input field
     * 
     * @param string $name Field name
     * @param array $config Field configuration
     */
    public function __construct(string $name, array $config = [])
    {
        // Validate input type
        $inputType = $config['type'] ?? 'text';
        if (!in_array($inputType, self::SUPPORTED_TYPES)) {
            $inputType = 'text';
        }
        
        $config['type'] = $inputType;
        
        parent::__construct($name, $config);
        
        // Add type-specific validation rules
        $this->addTypeSpecificValidation($inputType);
        
        // Handle type-specific configuration
        if (isset($config['min'])) {
            $this->setAttribute('min', $config['min']);
        }
        
        if (isset($config['max'])) {
            $this->setAttribute('max', $config['max']);
        }
        
        if (isset($config['step'])) {
            $this->setAttribute('step', $config['step']);
        }
        
        if (isset($config['pattern'])) {
            $this->setAttribute('pattern', $config['pattern']);
        }
        
        if (isset($config['maxlength'])) {
            $this->setAttribute('maxlength', $config['maxlength']);
        }
        
        if (isset($config['minlength'])) {
            $this->setAttribute('minlength', $config['minlength']);
        }
        
        if (isset($config['autocomplete'])) {
            $this->setAttribute('autocomplete', $config['autocomplete']);
        }
    }

    /**
     * Add type-specific validation rules
     * 
     * @param string $type Input type
     * @return void
     */
    private function addTypeSpecificValidation(string $type): void
    {
        switch ($type) {
            case 'email':
                if (!isset($this->validationRules['email'])) {
                    $this->addValidationRule('email', true);
                }
                break;
                
            case 'url':
                if (!isset($this->validationRules['url'])) {
                    $this->addValidationRule('url', true);
                }
                break;
                
            case 'number':
            case 'range':
                if (!isset($this->validationRules['numeric'])) {
                    $this->addValidationRule('numeric', true);
                }
                break;
                
            case 'tel':
                // Optional: Add phone number validation
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected static function getDefaultType(): string
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     */
    protected function renderDefault(array $context): string
    {
        $inputType = $this->getType();
        $value = $this->getValue();
        
        // Hidden fields don't need wrapping
        if ($inputType === 'hidden') {
            return sprintf(
                '<input type="hidden" %s value="%s">',
                $this->buildAttributesString(),
                $this->escape($value)
            );
        }
        
        // Build full field HTML with label, input, help text, and errors
        $html = [];
        
        // Field wrapper
        $fieldClasses = ['form-field mb-3', 'form-field-' . $inputType];
        if (isset($context['errors'][$this->name])) {
            $fieldClasses[] = 'has-error';
        }
        $html[] = sprintf('<div class="%s">', implode(' ', $fieldClasses));
        
        // Label
        if ($this->label) {
            $requiredMark = $this->required ? ' <span class="required">*</span>' : '';
            $html[] = sprintf(
                '<label for="%s" class="form-label">%s%s</label>',
                $this->getAttribute('id'),
                $this->escape($this->label),
                $requiredMark
            );
        }
        
        // Input element
        $html[] = sprintf(
            '<input type="%s" %s value="%s">',
            $inputType,
            $this->buildAttributesString(),
            $this->escape($value)
        );
        
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
     * {@inheritdoc}
     */
    public function validate(mixed $value = null): ValidationResult
    {
        $result = parent::validate($value);
        
        if ($result->isFailed()) {
            return $result;
        }
        
        $valueToValidate = $value ?? $this->value;
        
        // Type-specific validation
        switch ($this->type) {
            case 'email':
                if ($valueToValidate && !filter_var($valueToValidate, FILTER_VALIDATE_EMAIL)) {
                    $result->addError($this->name, 'Please enter a valid email address.');
                }
                break;
                
            case 'url':
                if ($valueToValidate && !filter_var($valueToValidate, FILTER_VALIDATE_URL)) {
                    $result->addError($this->name, 'Please enter a valid URL.');
                }
                break;
                
            case 'number':
                if ($valueToValidate !== null && $valueToValidate !== '' && !is_numeric($valueToValidate)) {
                    $result->addError($this->name, 'Please enter a valid number.');
                } else {
                    // Check min/max
                    $numValue = (float)$valueToValidate;
                    $min = $this->getAttribute('min');
                    $max = $this->getAttribute('max');
                    
                    if ($min !== null && $numValue < (float)$min) {
                        $result->addError($this->name, "Value must be at least {$min}.");
                    }
                    
                    if ($max !== null && $numValue > (float)$max) {
                        $result->addError($this->name, "Value must not exceed {$max}.");
                    }
                }
                break;
        }
        
        // Check minlength/maxlength for text inputs
        if (is_string($valueToValidate)) {
            $minLength = $this->getAttribute('minlength');
            $maxLength = $this->getAttribute('maxlength');
            $length = mb_strlen($valueToValidate);
            
            if ($minLength !== null && $length < (int)$minLength) {
                $result->addError($this->name, "Must be at least {$minLength} characters.");
            }
            
            if ($maxLength !== null && $length > (int)$maxLength) {
                $result->addError($this->name, "Must not exceed {$maxLength} characters.");
            }
        }
        
        // Check pattern if specified
        $pattern = $this->getAttribute('pattern');
        if ($pattern && $valueToValidate && !preg_match('/' . $pattern . '/', $valueToValidate)) {
            $result->addError($this->name, 'Value does not match the required pattern.');
        }
        
        return $result;
    }

    /**
     * Create a text input field
     * 
     * @param string $name Field name
     * @param array $config Field configuration
     * @return self
     */
    public static function text(string $name, array $config = []): self
    {
        $config['type'] = 'text';
        return new self($name, $config);
    }

    /**
     * Create an email input field
     * 
     * @param string $name Field name
     * @param array $config Field configuration
     * @return self
     */
    public static function email(string $name, array $config = []): self
    {
        $config['type'] = 'email';
        return new self($name, $config);
    }

    /**
     * Create a password input field
     * 
     * @param string $name Field name
     * @param array $config Field configuration
     * @return self
     */
    public static function password(string $name, array $config = []): self
    {
        $config['type'] = 'password';
        return new self($name, $config);
    }

    /**
     * Create a number input field
     * 
     * @param string $name Field name
     * @param array $config Field configuration
     * @return self
     */
    public static function number(string $name, array $config = []): self
    {
        $config['type'] = 'number';
        return new self($name, $config);
    }

    /**
     * Create a hidden input field
     * 
     * @param string $name Field name
     * @param mixed $value Field value
     * @return self
     */
    public static function hidden(string $name, mixed $value = null): self
    {
        return new self($name, ['type' => 'hidden', 'value' => $value]);
    }

    /**
     * Create a date input field
     * 
     * @param string $name Field name
     * @param array $config Field configuration
     * @return self
     */
    public static function date(string $name, array $config = []): self
    {
        $config['type'] = 'date';
        return new self($name, $config);
    }

    /**
     * Create a tel input field
     * 
     * @param string $name Field name
     * @param array $config Field configuration
     * @return self
     */
    public static function tel(string $name, array $config = []): self
    {
        $config['type'] = 'tel';
        return new self($name, $config);
    }
}
