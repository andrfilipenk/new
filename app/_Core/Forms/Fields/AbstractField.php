<?php
/**
 * AbstractField Class
 * 
 * Base implementation for all form fields, providing common functionality
 * for value management, validation, attributes, and rendering.
 * 
 * @package Core\Forms\Fields
 * @since 2.0.0
 */

namespace Core\Forms\Fields;

use Core\Forms\Validation\ValidationResult;

abstract class AbstractField implements FieldInterface
{
    /**
     * @var string Field name/identifier
     */
    protected string $name;

    /**
     * @var string Field type
     */
    protected string $type;

    /**
     * @var mixed Field value
     */
    protected mixed $value = null;

    /**
     * @var array HTML attributes
     */
    protected array $attributes = [];

    /**
     * @var array Validation rules
     */
    protected array $validationRules = [];

    /**
     * @var bool Whether field is required
     */
    protected bool $required = false;

    /**
     * @var string|null Field label
     */
    protected ?string $label = null;

    /**
     * @var string|null Help text
     */
    protected ?string $helpText = null;

    /**
     * @var string|null Placeholder text
     */
    protected ?string $placeholder = null;

    /**
     * @var string|null Custom template path
     */
    protected ?string $template = null;

    /**
     * @var array CSS classes
     */
    protected array $cssClasses = [];

    /**
     * @var array Custom data attributes
     */
    protected array $dataAttributes = [];

    /**
     * Create a new field instance
     * 
     * @param string $name Field name
     * @param array $config Field configuration
     */
    public function __construct(string $name, array $config = [])
    {
        $this->name = $name;
        $this->type = $config['type'] ?? static::getDefaultType();
        
        // Set initial configuration
        if (isset($config['value'])) {
            $this->setValue($config['value']);
        }
        
        if (isset($config['label'])) {
            $this->setLabel($config['label']);
        }
        
        if (isset($config['placeholder'])) {
            $this->setPlaceholder($config['placeholder']);
        }
        
        if (isset($config['helpText'])) {
            $this->setHelpText($config['helpText']);
        }
        
        if (isset($config['required'])) {
            $this->setRequired($config['required']);
        }
        
        if (isset($config['attributes'])) {
            $this->setAttributes($config['attributes']);
        }
        
        if (isset($config['class'])) {
            $this->addClass($config['class']);
        }
        
        if (isset($config['validationRules'])) {
            foreach ($config['validationRules'] as $ruleName => $ruleConfig) {
                $this->addValidationRule($ruleName, $ruleConfig);
            }
        }
        
        if (isset($config['template'])) {
            $this->template = $config['template'];
        }
    }

    /**
     * Get the default field type
     * 
     * @return string
     */
    protected static function getDefaultType(): string
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(mixed $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        $attributes = $this->attributes;
        
        // Add name attribute
        $attributes['name'] = $this->name;
        
        // Add ID if not set
        if (!isset($attributes['id'])) {
            $attributes['id'] = $this->generateId();
        }
        
        // Add placeholder
        if ($this->placeholder && !isset($attributes['placeholder'])) {
            $attributes['placeholder'] = $this->placeholder;
        }
        
        // Add required attribute
        if ($this->required && !isset($attributes['required'])) {
            $attributes['required'] = 'required';
        }
        
        // Add CSS classes
        if (!empty($this->cssClasses)) {
            $existingClasses = $attributes['class'] ?? '';
            $attributes['class'] = trim($existingClasses . ' ' . implode(' ', $this->cssClasses));
        }
        
        // Add data attributes
        foreach ($this->dataAttributes as $key => $value) {
            $attributes['data-' . $key] = $value;
        }
        
        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttribute(string $name, mixed $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationRules(): array
    {
        return $this->validationRules;
    }

    /**
     * {@inheritdoc}
     */
    public function addValidationRule(string $ruleName, mixed $ruleConfig = []): self
    {
        $this->validationRules[$ruleName] = $ruleConfig;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * {@inheritdoc}
     */
    public function setRequired(bool $required = true): self
    {
        $this->required = $required;
        
        if ($required) {
            $this->addValidationRule('required', true);
        } else {
            unset($this->validationRules['required']);
        }
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * {@inheritdoc}
     */
    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHelpText(): ?string
    {
        return $this->helpText;
    }

    /**
     * {@inheritdoc}
     */
    public function setHelpText(string $helpText): self
    {
        $this->helpText = $helpText;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    /**
     * {@inheritdoc}
     */
    public function setPlaceholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * Add a CSS class to this field
     * 
     * @param string $class CSS class name
     * @return self
     */
    public function addClass(string $class): self
    {
        $classes = array_filter(explode(' ', $class));
        foreach ($classes as $cls) {
            if (!in_array($cls, $this->cssClasses)) {
                $this->cssClasses[] = $cls;
            }
        }
        return $this;
    }

    /**
     * Remove a CSS class from this field
     * 
     * @param string $class CSS class name
     * @return self
     */
    public function removeClass(string $class): self
    {
        $this->cssClasses = array_filter($this->cssClasses, fn($c) => $c !== $class);
        return $this;
    }

    /**
     * Set a data attribute
     * 
     * @param string $name Data attribute name (without 'data-' prefix)
     * @param mixed $value Data attribute value
     * @return self
     */
    public function setDataAttribute(string $name, mixed $value): self
    {
        $this->dataAttributes[$name] = $value;
        return $this;
    }

    /**
     * Get custom template path
     * 
     * @return string|null
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * Set custom template path
     * 
     * @param string $template Template path
     * @return self
     */
    public function setTemplate(string $template): self
    {
        $this->template = $template;
        return $this;
    }

    /**
     * Generate a default ID for this field
     * 
     * @return string
     */
    protected function generateId(): string
    {
        // Convert field name to valid ID (replace brackets and dots)
        return 'field_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $this->name);
    }

    /**
     * Build HTML attributes string
     * 
     * @param array|null $additionalAttributes Additional attributes to include
     * @return string
     */
    protected function buildAttributesString(?array $additionalAttributes = null): string
    {
        $attributes = $this->getAttributes();
        
        if ($additionalAttributes) {
            $attributes = array_merge($attributes, $additionalAttributes);
        }
        
        $parts = [];
        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            
            if ($value === true) {
                $parts[] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            } else {
                $parts[] = sprintf(
                    '%s="%s"',
                    htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
                );
            }
        }
        
        return implode(' ', $parts);
    }

    /**
     * Escape HTML output
     * 
     * @param mixed $value Value to escape
     * @return string
     */
    protected function escape(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * {@inheritdoc}
     * 
     * Default implementation - subclasses should override
     */
    public function render(array $context = []): string
    {
        // If custom template is specified, use template rendering
        if ($this->template) {
            return $this->renderWithTemplate($context);
        }
        
        // Default simple rendering
        return $this->renderDefault($context);
    }

    /**
     * Render using custom template
     * 
     * @param array $context Rendering context
     * @return string
     */
    protected function renderWithTemplate(array $context): string
    {
        // This will be implemented when FormRenderer is created
        // For now, fall back to default rendering
        return $this->renderDefault($context);
    }

    /**
     * Default rendering implementation
     * 
     * @param array $context Rendering context
     * @return string
     */
    abstract protected function renderDefault(array $context): string;

    /**
     * {@inheritdoc}
     * 
     * Default implementation - subclasses can override for custom validation
     */
    public function validate(mixed $value = null): ValidationResult
    {
        $valueToValidate = $value ?? $this->value;
        
        // Basic required validation
        if ($this->required && empty($valueToValidate)) {
            return ValidationResult::failure([
                $this->name => ['This field is required.']
            ]);
        }
        
        // Additional validation will be handled by ValidationPipeline
        return ValidationResult::success();
    }
}
