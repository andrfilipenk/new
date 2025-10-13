<?php
/**
 * TextAreaField Class
 * 
 * Represents HTML textarea fields for multi-line text input.
 * 
 * @package Core\Forms\Fields
 * @since 2.0.0
 */

namespace Core\Forms\Fields;

class TextAreaField extends AbstractField
{
    /**
     * {@inheritdoc}
     */
    protected static function getDefaultType(): string
    {
        return 'textarea';
    }

    /**
     * Create a new textarea field
     * 
     * @param string $name Field name
     * @param array $config Field configuration
     */
    public function __construct(string $name, array $config = [])
    {
        parent::__construct($name, $config);
        
        if (isset($config['rows'])) {
            $this->setAttribute('rows', $config['rows']);
        }
        
        if (isset($config['cols'])) {
            $this->setAttribute('cols', $config['cols']);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function renderDefault(array $context): string
    {
        $html = [];
        
        // Field wrapper
        $fieldClasses = ['form-field', 'form-field-textarea'];
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
        
        // Textarea element
        $html[] = sprintf(
            '<textarea %s>%s</textarea>',
            $this->buildAttributesString(),
            $this->escape($this->value)
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
        
        $html[] = '</div>';
        
        return implode("\n", $html);
    }

    /**
     * Create a textarea field
     * 
     * @param string $name Field name
     * @param array $config Field configuration
     * @return self
     */
    public static function make(string $name, array $config = []): self
    {
        return new self($name, $config);
    }
}
