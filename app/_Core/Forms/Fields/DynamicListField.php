<?php
/**
 * DynamicListField Class
 * 
 * Field that allows users to add, remove, and reorder multiple items.
 * Useful for things like multiple phone numbers, email addresses, or any
 * repeating data structure.
 * 
 * @package Core\Forms\Fields
 * @since 2.0.0
 */

namespace Core\Forms\Fields;

use Core\Forms\Validation\ValidationResult;

class DynamicListField extends AbstractField
{
    /**
     * @var FieldInterface Field template for items
     */
    private FieldInterface $itemTemplate;

    /**
     * @var int Minimum number of items
     */
    private int $minItems = 0;

    /**
     * @var int Maximum number of items
     */
    private int $maxItems = 10;

    /**
     * @var int Initial number of items to show
     */
    private int $initialItems = 1;

    /**
     * @var bool Whether items can be reordered
     */
    private bool $allowReorder = true;

    /**
     * @var string Add button text
     */
    private string $addButtonText = 'Add Item';

    /**
     * @var string Remove button text
     */
    private string $removeButtonText = 'Remove';

    /**
     * @var string Item label template (use {index} placeholder)
     */
    private ?string $itemLabelTemplate = null;

    /**
     * @var array Current items
     */
    private array $items = [];

    /**
     * {@inheritdoc}
     */
    protected static function getDefaultType(): string
    {
        return 'dynamic-list';
    }

    /**
     * Set the item template
     * 
     * @param FieldInterface $template Field template
     * @return self
     */
    public function setItemTemplate(FieldInterface $template): self
    {
        $this->itemTemplate = $template;
        return $this;
    }

    /**
     * Get item template
     * 
     * @return FieldInterface
     */
    public function getItemTemplate(): FieldInterface
    {
        return $this->itemTemplate;
    }

    /**
     * Set minimum items
     * 
     * @param int $min Minimum items
     * @return self
     */
    public function setMinItems(int $min): self
    {
        $this->minItems = max(0, $min);
        return $this;
    }

    /**
     * Set maximum items
     * 
     * @param int $max Maximum items
     * @return self
     */
    public function setMaxItems(int $max): self
    {
        $this->maxItems = max(1, $max);
        return $this;
    }

    /**
     * Set initial items count
     * 
     * @param int $count Initial items
     * @return self
     */
    public function setInitialItems(int $count): self
    {
        $this->initialItems = max(1, $count);
        return $this;
    }

    /**
     * Set whether items can be reordered
     * 
     * @param bool $allow Allow reordering
     * @return self
     */
    public function setAllowReorder(bool $allow): self
    {
        $this->allowReorder = $allow;
        return $this;
    }

    /**
     * Set add button text
     * 
     * @param string $text Button text
     * @return self
     */
    public function setAddButtonText(string $text): self
    {
        $this->addButtonText = $text;
        return $this;
    }

    /**
     * Set remove button text
     * 
     * @param string $text Button text
     * @return self
     */
    public function setRemoveButtonText(string $text): self
    {
        $this->removeButtonText = $text;
        return $this;
    }

    /**
     * Set item label template
     * 
     * @param string|null $template Template with {index} placeholder
     * @return self
     */
    public function setItemLabelTemplate(?string $template): self
    {
        $this->itemLabelTemplate = $template;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(): mixed
    {
        return $this->items;
    }

    /**
     * {@inheritdoc}
     */
    public function setValue(mixed $value): self
    {
        if (!is_array($value)) {
            $value = [];
        }

        $this->items = $value;
        $this->value = $value;
        
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function render(array $context = []): string
    {
        $theme = $context['theme'] ?? null;
        
        if ($theme && method_exists($theme, 'renderDynamicListField')) {
            return $theme->renderDynamicListField($this, $context, []);
        }
        
        return $this->renderDefault($context);
    }

    /**
     * Render default dynamic list field
     * 
     * @param array $context Rendering context
     * @return string HTML output
     */
    protected function renderDefault(array $context): string
    {
        $fieldId = $this->getAttribute('id', $this->name);
        $errors = $context['errors'][$this->name] ?? [];
        if (!is_array($errors)) {
            $errors = [$errors];
        }
        $hasErrors = !empty($errors);

        // Ensure we have at least initialItems
        $itemCount = max(count($this->items), $this->initialItems);
        
        $html = [];
        
        // Wrapper
        $html[] = sprintf(
            '<div class="dynamic-list-field%s" data-field-name="%s" data-min-items="%d" data-max-items="%d">',
            $hasErrors ? ' has-error' : '',
            htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8'),
            $this->minItems,
            $this->maxItems
        );
        
        // Label
        if ($this->label) {
            $html[] = sprintf(
                '<label class="field-label">%s%s</label>',
                htmlspecialchars($this->label, ENT_QUOTES, 'UTF-8'),
                $this->required ? ' <span class="required-indicator">*</span>' : ''
            );
        }
        
        // Help text
        if ($this->helpText) {
            $html[] = sprintf(
                '<small class="field-help">%s</small>',
                htmlspecialchars($this->helpText, ENT_QUOTES, 'UTF-8')
            );
        }
        
        // Items container
        $html[] = '<div class="dynamic-list-items" data-items-container>';
        
        // Render items
        for ($i = 0; $i < $itemCount; $i++) {
            $html[] = $this->renderItem($i, $context);
        }
        
        $html[] = '</div>'; // .dynamic-list-items
        
        // Add button
        if ($itemCount < $this->maxItems) {
            $html[] = sprintf(
                '<button type="button" class="btn-add-item" data-add-item>%s</button>',
                htmlspecialchars($this->addButtonText, ENT_QUOTES, 'UTF-8')
            );
        }
        
        // Item template (hidden, for JavaScript cloning)
        $html[] = '<template data-item-template>';
        $html[] = $this->renderItem('{{INDEX}}', $context, true);
        $html[] = '</template>';
        
        // Errors
        if ($hasErrors) {
            $html[] = '<div class="field-errors">';
            foreach ($errors as $error) {
                $html[] = sprintf(
                    '<div class="field-error">%s</div>',
                    htmlspecialchars($error, ENT_QUOTES, 'UTF-8')
                );
            }
            $html[] = '</div>';
        }
        
        $html[] = '</div>'; // .dynamic-list-field
        
        return implode("\n", $html);
    }

    /**
     * Render a single list item
     * 
     * @param int|string $index Item index
     * @param array $context Rendering context
     * @param bool $isTemplate Whether this is a template
     * @return string HTML output
     */
    private function renderItem(int|string $index, array $context, bool $isTemplate = false): string
    {
        $html = [];
        
        $html[] = sprintf(
            '<div class="dynamic-list-item" data-item-index="%s">',
            htmlspecialchars((string)$index, ENT_QUOTES, 'UTF-8')
        );
        
        // Reorder handle
        if ($this->allowReorder) {
            $html[] = '<span class="item-handle" data-handle>⋮⋮</span>';
        }
        
        // Item label
        if ($this->itemLabelTemplate) {
            $label = str_replace('{index}', (string)($index + 1), $this->itemLabelTemplate);
            $html[] = sprintf(
                '<span class="item-label">%s</span>',
                htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
            );
        }
        
        // Clone template field
        $itemField = clone $this->itemTemplate;
        $itemField->setAttribute('name', "{$this->name}[{$index}]");
        $itemField->setAttribute('id', "{$this->name}_{$index}");
        
        // Set value if not template
        if (!$isTemplate && isset($this->items[$index])) {
            $itemField->setValue($this->items[$index]);
        }
        
        // Render the field
        $html[] = '<div class="item-field">';
        $html[] = $itemField->render($context);
        $html[] = '</div>';
        
        // Remove button
        $html[] = sprintf(
            '<button type="button" class="btn-remove-item" data-remove-item>%s</button>',
            htmlspecialchars($this->removeButtonText, ENT_QUOTES, 'UTF-8')
        );
        
        $html[] = '</div>'; // .dynamic-list-item
        
        return implode("\n", $html);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(mixed $value = null): ValidationResult
    {
        $value = $value ?? $this->items;
        
        if (!is_array($value)) {
            $value = [];
        }
        
        $errors = [];
        
        // Validate count
        $count = count($value);
        
        if ($count < $this->minItems) {
            $errors['min_items'] = sprintf(
                'At least %d item%s required',
                $this->minItems,
                $this->minItems !== 1 ? 's' : ''
            );
        }
        
        if ($count > $this->maxItems) {
            $errors['max_items'] = sprintf(
                'Maximum %d item%s allowed',
                $this->maxItems,
                $this->maxItems !== 1 ? 's' : ''
            );
        }
        
        // Validate each item
        foreach ($value as $index => $itemValue) {
            $itemField = clone $this->itemTemplate;
            $result = $itemField->validate($itemValue);
            
            if (!$result->isValid()) {
                foreach ($result->getErrors() as $key => $message) {
                    $errors["item_{$index}_{$key}"] = "Item " . ($index + 1) . ": {$message}";
                }
            }
        }
        
        return empty($errors) 
            ? ValidationResult::success() 
            : ValidationResult::failure($errors);
    }

    /**
     * Static factory for email list
     * 
     * @param string $name Field name
     * @param array $config Configuration
     * @return self
     */
    public static function emails(string $name, array $config = []): self
    {
        $field = new self($name, $config);
        $field->setItemTemplate(
            InputField::email('email', [
                'placeholder' => 'email@example.com',
                'required' => true
            ])
        );
        $field->setLabel($config['label'] ?? 'Email Addresses');
        $field->setItemLabelTemplate('Email #{index}');
        
        return $field;
    }

    /**
     * Static factory for phone list
     * 
     * @param string $name Field name
     * @param array $config Configuration
     * @return self
     */
    public static function phones(string $name, array $config = []): self
    {
        $field = new self($name, $config);
        $field->setItemTemplate(
            InputField::tel('phone', [
                'placeholder' => '555-1234',
                'required' => true
            ])
        );
        $field->setLabel($config['label'] ?? 'Phone Numbers');
        $field->setItemLabelTemplate('Phone #{index}');
        
        return $field;
    }

    /**
     * Static factory for URL list
     * 
     * @param string $name Field name
     * @param array $config Configuration
     * @return self
     */
    public static function urls(string $name, array $config = []): self
    {
        $field = new self($name, $config);
        $field->setItemTemplate(
            InputField::url('url', [
                'placeholder' => 'https://example.com',
                'required' => true
            ])
        );
        $field->setLabel($config['label'] ?? 'URLs');
        $field->setItemLabelTemplate('URL #{index}');
        
        return $field;
    }

    /**
     * Static factory for custom item template
     * 
     * @param string $name Field name
     * @param FieldInterface $template Item template field
     * @param array $config Configuration
     * @return self
     */
    public static function custom(string $name, FieldInterface $template, array $config = []): self
    {
        $field = new self($name, $config);
        $field->setItemTemplate($template);
        
        return $field;
    }
}
