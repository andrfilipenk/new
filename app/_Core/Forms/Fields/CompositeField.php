<?php
/**
 * CompositeField Class
 * 
 * Groups multiple fields together as a single logical unit.
 * Useful for creating reusable field groups like addresses, date/time pickers,
 * or custom compound inputs. Supports nested validation and rendering.
 * 
 * @package Core\Forms\Fields
 * @since 2.0.0
 */

namespace Core\Forms\Fields;

use Core\Forms\Validation\ValidationResult;

class CompositeField extends AbstractField
{
    /**
     * @var array<FieldInterface> Child fields
     */
    private array $fields = [];

    /**
     * @var string Rendering mode ('inline', 'stacked', 'grid')
     */
    private string $renderMode = 'stacked';

    /**
     * @var string Wrapper HTML tag
     */
    private string $wrapperTag = 'div';

    /**
     * @var array Wrapper CSS classes
     */
    private array $wrapperClasses = ['composite-field'];

    /**
     * @var bool Whether to render field labels
     */
    private bool $showFieldLabels = true;

    /**
     * @var string|null Custom field separator
     */
    private ?string $fieldSeparator = null;

    /**
     * @var bool Whether to propagate errors to parent
     */
    private bool $propagateErrors = true;

    /**
     * {@inheritdoc}
     */
    protected static function getDefaultType(): string
    {
        return 'composite';
    }

    /**
     * Add a field to this composite
     * 
     * @param FieldInterface $field Field to add
     * @param string|null $key Optional key (uses field name if not provided)
     * @return self
     */
    public function addField(FieldInterface $field, ?string $key = null): self
    {
        $key = $key ?? $field->getName();
        $this->fields[$key] = $field;
        return $this;
    }

    /**
     * Remove a field from this composite
     * 
     * @param string $key Field key
     * @return self
     */
    public function removeField(string $key): self
    {
        unset($this->fields[$key]);
        return $this;
    }

    /**
     * Get a field by key
     * 
     * @param string $key Field key
     * @return FieldInterface|null
     */
    public function getField(string $key): ?FieldInterface
    {
        return $this->fields[$key] ?? null;
    }

    /**
     * Get all fields
     * 
     * @return array<FieldInterface>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Check if field exists
     * 
     * @param string $key Field key
     * @return bool
     */
    public function hasField(string $key): bool
    {
        return isset($this->fields[$key]);
    }

    /**
     * Set the render mode
     * 
     * @param string $mode Render mode ('inline', 'stacked', 'grid')
     * @return self
     */
    public function setRenderMode(string $mode): self
    {
        if (!in_array($mode, ['inline', 'stacked', 'grid'], true)) {
            throw new \InvalidArgumentException("Invalid render mode: {$mode}");
        }
        
        $this->renderMode = $mode;
        return $this;
    }

    /**
     * Get the render mode
     * 
     * @return string
     */
    public function getRenderMode(): string
    {
        return $this->renderMode;
    }

    /**
     * Set wrapper tag
     * 
     * @param string $tag HTML tag name
     * @return self
     */
    public function setWrapperTag(string $tag): self
    {
        $this->wrapperTag = $tag;
        return $this;
    }

    /**
     * Add wrapper CSS class
     * 
     * @param string $class CSS class
     * @return self
     */
    public function addWrapperClass(string $class): self
    {
        $this->wrapperClasses[] = $class;
        return $this;
    }

    /**
     * Set whether to show field labels
     * 
     * @param bool $show Whether to show labels
     * @return self
     */
    public function setShowFieldLabels(bool $show): self
    {
        $this->showFieldLabels = $show;
        return $this;
    }

    /**
     * Set field separator
     * 
     * @param string|null $separator HTML separator
     * @return self
     */
    public function setFieldSeparator(?string $separator): self
    {
        $this->fieldSeparator = $separator;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(): mixed
    {
        $values = [];
        
        foreach ($this->fields as $key => $field) {
            $values[$key] = $field->getValue();
        }
        
        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(mixed $value): self
    {
        if (!is_array($value)) {
            return $this;
        }
        
        foreach ($value as $key => $fieldValue) {
            if (isset($this->fields[$key])) {
                $this->fields[$key]->setValue($fieldValue);
            }
        }
        
        $this->value = $value;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function render(array $context = []): string
    {
        $theme = $context['theme'] ?? null;
        
        if ($theme && method_exists($theme, 'renderCompositeField')) {
            return $theme->renderCompositeField($this, $context, []);
        }
        
        return $this->renderDefault($context);
    }

    /**
     * Render default composite field
     * 
     * @param array $context Rendering context
     * @return string HTML output
     */
    protected function renderDefault(array $context): string
    {
        $html = [];
        
        // Add render mode class
        $this->wrapperClasses[] = 'composite-' . $this->renderMode;
        
        // Wrapper opening tag
        $wrapperClasses = implode(' ', array_unique($this->wrapperClasses));
        $html[] = sprintf(
            '<%s class="%s" data-composite-field="%s">',
            $this->wrapperTag,
            htmlspecialchars($wrapperClasses, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8')
        );
        
        // Composite label
        if ($this->label) {
            $html[] = sprintf(
                '<div class="composite-label">%s</div>',
                htmlspecialchars($this->label, ENT_QUOTES, 'UTF-8')
            );
        }
        
        // Fields container
        $html[] = '<div class="composite-fields">';
        
        $fieldHtmlParts = [];
        
        foreach ($this->fields as $key => $field) {
            $fieldHtml = [];
            
            $fieldHtml[] = sprintf(
                '<div class="composite-field-item" data-field-key="%s">',
                htmlspecialchars($key, ENT_QUOTES, 'UTF-8')
            );
            
            // Field label
            if ($this->showFieldLabels && $field->getLabel()) {
                $fieldHtml[] = sprintf(
                    '<label for="%s">%s</label>',
                    htmlspecialchars($field->getAttribute('id', $field->getName()), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($field->getLabel(), ENT_QUOTES, 'UTF-8')
                );
            }
            
            // Render the field
            $fieldHtml[] = $field->render($context);
            
            // Field help text
            if ($field->getHelpText()) {
                $fieldHtml[] = sprintf(
                    '<small class="field-help">%s</small>',
                    htmlspecialchars($field->getHelpText(), ENT_QUOTES, 'UTF-8')
                );
            }
            
            // Field errors
            if (isset($context['errors'][$field->getName()])) {
                $errors = (array) $context['errors'][$field->getName()];
                foreach ($errors as $error) {
                    $fieldHtml[] = sprintf(
                        '<div class="field-error">%s</div>',
                        htmlspecialchars($error, ENT_QUOTES, 'UTF-8')
                    );
                }
            }
            
            $fieldHtml[] = '</div>'; // .composite-field-item
            
            $fieldHtmlParts[] = implode("\n", $fieldHtml);
        }
        
        // Join fields with separator
        if ($this->fieldSeparator !== null) {
            $html[] = implode($this->fieldSeparator, $fieldHtmlParts);
        } else {
            $html[] = implode("\n", $fieldHtmlParts);
        }
        
        $html[] = '</div>'; // .composite-fields
        
        // Composite help text
        if ($this->helpText) {
            $html[] = sprintf(
                '<small class="composite-help">%s</small>',
                htmlspecialchars($this->helpText, ENT_QUOTES, 'UTF-8')
            );
        }
        
        $html[] = sprintf('</%s>', $this->wrapperTag);
        
        return implode("\n", $html);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(mixed $value = null): ValidationResult
    {
        $value = $value ?? $this->getValue();
        
        if (!is_array($value)) {
            $value = [];
        }
        
        $hasErrors = false;
        $allErrors = [];
        
        // Validate each child field
        foreach ($this->fields as $key => $field) {
            $fieldValue = $value[$key] ?? null;
            $result = $field->validate($fieldValue);
            
            if (!$result->isValid()) {
                $hasErrors = true;
                
                if ($this->propagateErrors) {
                    // Propagate errors with field key prefix
                    foreach ($result->getErrors() as $errorKey => $errorMessage) {
                        $allErrors["{$key}.{$errorKey}"] = $errorMessage;
                    }
                } else {
                    // Keep errors under field key
                    $allErrors[$key] = $result->getErrors();
                }
            }
        }
        
        // Check composite-level required validation
        if ($this->required && empty(array_filter($value))) {
            $hasErrors = true;
            $allErrors['required'] = sprintf(
                '%s is required',
                $this->label ?? $this->name
            );
        }
        
        return $hasErrors 
            ? ValidationResult::failure($allErrors)
            : ValidationResult::success();
    }

    /**
     * Create an address composite field
     * 
     * @param string $name Field name
     * @param array $config Configuration
     * @return self
     */
    public static function address(string $name, array $config = []): self
    {
        $field = new self($name, array_merge($config, ['type' => 'composite']));
        
        // Add address fields
        $field->addField(
            InputField::text('street', [
                'label' => 'Street Address',
                'required' => true,
                'placeholder' => '123 Main St'
            ])
        );
        
        $field->addField(
            InputField::text('city', [
                'label' => 'City',
                'required' => true,
                'placeholder' => 'New York'
            ])
        );
        
        $field->addField(
            InputField::text('state', [
                'label' => 'State/Province',
                'required' => true,
                'placeholder' => 'NY'
            ])
        );
        
        $field->addField(
            InputField::text('postal_code', [
                'label' => 'Postal Code',
                'required' => true,
                'placeholder' => '10001'
            ])
        );
        
        $field->addField(
            InputField::text('country', [
                'label' => 'Country',
                'required' => true,
                'placeholder' => 'USA',
                'value' => 'USA'
            ])
        );
        
        $field->setLabel($config['label'] ?? 'Address');
        
        return $field;
    }

    /**
     * Create a name composite field
     * 
     * @param string $name Field name
     * @param array $config Configuration
     * @return self
     */
    public static function fullName(string $name, array $config = []): self
    {
        $field = new self($name, array_merge($config, ['type' => 'composite']));
        
        $field->addField(
            InputField::text('first_name', [
                'label' => 'First Name',
                'required' => true,
                'placeholder' => 'John'
            ])
        );
        
        $field->addField(
            InputField::text('middle_name', [
                'label' => 'Middle Name',
                'placeholder' => 'M.'
            ])
        );
        
        $field->addField(
            InputField::text('last_name', [
                'label' => 'Last Name',
                'required' => true,
                'placeholder' => 'Doe'
            ])
        );
        
        $field->setLabel($config['label'] ?? 'Full Name');
        $field->setRenderMode('inline');
        
        return $field;
    }

    /**
     * Create a date range composite field
     * 
     * @param string $name Field name
     * @param array $config Configuration
     * @return self
     */
    public static function dateRange(string $name, array $config = []): self
    {
        $field = new self($name, array_merge($config, ['type' => 'composite']));
        
        $field->addField(
            InputField::date('start_date', [
                'label' => 'Start Date',
                'required' => true
            ])
        );
        
        $field->addField(
            InputField::date('end_date', [
                'label' => 'End Date',
                'required' => true
            ])
        );
        
        $field->setLabel($config['label'] ?? 'Date Range');
        $field->setRenderMode('inline');
        $field->setFieldSeparator(' <span class="separator">to</span> ');
        
        return $field;
    }

    /**
     * Create a phone number composite field
     * 
     * @param string $name Field name
     * @param array $config Configuration
     * @return self
     */
    public static function phoneNumber(string $name, array $config = []): self
    {
        $field = new self($name, array_merge($config, ['type' => 'composite']));
        
        $field->addField(
            SelectField::create('country_code', [
                ['+1', 'US/Canada (+1)'],
                ['+44', 'UK (+44)'],
                ['+61', 'Australia (+61)'],
                ['+91', 'India (+91)']
            ], [
                'label' => 'Code',
                'value' => '+1'
            ])
        );
        
        $field->addField(
            InputField::tel('number', [
                'label' => 'Number',
                'required' => true,
                'placeholder' => '555-1234'
            ])
        );
        
        $field->setLabel($config['label'] ?? 'Phone Number');
        $field->setRenderMode('inline');
        
        return $field;
    }

    /**
     * Create a custom composite field with specified fields
     * 
     * @param string $name Field name
     * @param array $fields Array of FieldInterface instances
     * @param array $config Configuration
     * @return self
     */
    public static function custom(string $name, array $fields, array $config = []): self
    {
        $field = new self($name, array_merge($config, ['type' => 'composite']));
        
        foreach ($fields as $key => $subField) {
            if ($subField instanceof FieldInterface) {
                $field->addField($subField, is_string($key) ? $key : null);
            }
        }
        
        return $field;
    }
}
