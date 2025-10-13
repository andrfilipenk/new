<?php
/**
 * FormRenderer Class
 * 
 * Handles form rendering with theme support and template processing.
 * Generates HTML output from form definitions.
 * 
 * @package Core\Forms\Rendering
 * @since 2.0.0
 */

namespace Core\Forms\Rendering;

use Core\Forms\FormDefinition;
use Core\Forms\Fields\FieldInterface;

class FormRenderer
{
    /**
     * @var ThemeManager Theme manager
     */
    private ThemeManager $themeManager;

    /**
     * @var string Current theme
     */
    private string $currentTheme = 'default';

    /**
     * @var array Rendering options
     */
    private array $options = [
        'show_labels' => true,
        'show_errors' => true,
        'show_help_text' => true,
        'error_position' => 'below', // 'below', 'above', 'inline'
        'field_wrapper' => 'div',
        'label_position' => 'above', // 'above', 'inline'
    ];

    /**
     * Create a new form renderer
     * 
     * @param ThemeManager|null $themeManager Theme manager instance
     */
    public function __construct(?ThemeManager $themeManager = null)
    {
        $this->themeManager = $themeManager ?? new ThemeManager();
    }

    /**
     * Render a complete form
     * 
     * @param FormDefinition $form Form definition
     * @param array $context Rendering context (errors, values, etc.)
     * @param array $options Additional rendering options
     * @return string Rendered HTML
     */
    public function render(FormDefinition $form, array $context = [], array $options = []): string
    {
        $renderOptions = array_merge($this->options, $form->getRenderConfig(), $options);
        $html = [];

        // Form opening tag
        $html[] = $this->renderFormOpen($form);

        // CSRF field if enabled
        if ($form->isCsrfEnabled() && isset($context['csrf_field'])) {
            $html[] = $context['csrf_field'];
        }

        // Render all fields
        foreach ($form->getFields() as $field) {
            $html[] = $this->renderField($field, $context, $renderOptions);
        }

        // Submit button (if not explicitly hidden)
        if (!($options['hide_submit'] ?? false)) {
            $html[] = $this->renderSubmitButton($renderOptions);
        }

        // Form closing tag
        $html[] = '</form>';

        return implode("\n", $html);
    }

    /**
     * Render form opening tag
     * 
     * @param FormDefinition $form Form definition
     * @return string
     */
    private function renderFormOpen(FormDefinition $form): string
    {
        $attributes = $form->getAttributes();
        $attrString = $this->buildAttributesString($attributes);
        
        return "<form {$attrString}>";
    }

    /**
     * Render a single field
     * 
     * @param FieldInterface $field Field to render
     * @param array $context Rendering context
     * @param array $options Rendering options
     * @return string
     */
    public function renderField(FieldInterface $field, array $context = [], array $options = []): string
    {
        $fieldName = $field->getName();
        $fieldType = $field->getType();
        $hasError = isset($context['errors'][$fieldName]);

        // Check for custom template
        if ($field->getTemplate()) {
            return $this->renderWithTemplate($field, $context, $options);
        }

        // Use theme-based rendering
        return $this->renderWithTheme($field, $context, $options);
    }

    /**
     * Render field with custom template
     * 
     * @param FieldInterface $field Field to render
     * @param array $context Rendering context
     * @param array $options Rendering options
     * @return string
     */
    private function renderWithTemplate(FieldInterface $field, array $context, array $options): string
    {
        $template = $field->getTemplate();
        
        // Simple template processing (replace variables)
        $html = file_get_contents($template);
        
        $variables = [
            '{name}' => $field->getName(),
            '{label}' => $field->getLabel() ?? '',
            '{value}' => $this->escape($field->getValue()),
            '{type}' => $field->getType(),
            '{attributes}' => $this->buildFieldAttributesString($field),
            '{help_text}' => $field->getHelpText() ?? '',
            '{errors}' => $this->renderFieldErrors($field->getName(), $context),
        ];

        return str_replace(array_keys($variables), array_values($variables), $html);
    }

    /**
     * Render field using theme
     * 
     * @param FieldInterface $field Field to render
     * @param array $context Rendering context
     * @param array $options Rendering options
     * @return string
     */
    private function renderWithTheme(FieldInterface $field, array $context, array $options): string
    {
        $theme = $this->themeManager->getTheme($this->currentTheme);
        
        return $theme->renderField($field, $context, $options);
    }

    /**
     * Render field errors
     * 
     * @param string $fieldName Field name
     * @param array $context Rendering context
     * @return string
     */
    private function renderFieldErrors(string $fieldName, array $context): string
    {
        if (!isset($context['errors'][$fieldName])) {
            return '';
        }

        $errors = $context['errors'][$fieldName];
        $html = ['<div class="field-errors">'];

        foreach ($errors as $error) {
            $html[] = sprintf('<span class="error">%s</span>', $this->escape($error));
        }

        $html[] = '</div>';

        return implode("\n", $html);
    }

    /**
     * Render submit button
     * 
     * @param array $options Rendering options
     * @return string
     */
    private function renderSubmitButton(array $options): string
    {
        $text = $options['submit_text'] ?? 'Submit';
        $class = $options['submit_class'] ?? 'btn btn-primary';

        return sprintf(
            '<div class="form-submit"><button type="submit" class="%s">%s</button></div>',
            $this->escape($class),
            $this->escape($text)
        );
    }

    /**
     * Build HTML attributes string
     * 
     * @param array $attributes Attributes array
     * @return string
     */
    private function buildAttributesString(array $attributes): string
    {
        $parts = [];

        foreach ($attributes as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if ($value === true) {
                $parts[] = $this->escape($name);
            } else {
                $parts[] = sprintf(
                    '%s="%s"',
                    $this->escape($name),
                    $this->escape($value)
                );
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Build field attributes string
     * 
     * @param FieldInterface $field Field instance
     * @return string
     */
    private function buildFieldAttributesString(FieldInterface $field): string
    {
        return $this->buildAttributesString($field->getAttributes());
    }

    /**
     * Escape HTML output
     * 
     * @param mixed $value Value to escape
     * @return string
     */
    private function escape(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Set current theme
     * 
     * @param string $theme Theme name
     * @return self
     */
    public function setTheme(string $theme): self
    {
        $this->currentTheme = $theme;
        return $this;
    }

    /**
     * Get current theme
     * 
     * @return string
     */
    public function getTheme(): string
    {
        return $this->currentTheme;
    }

    /**
     * Set rendering options
     * 
     * @param array $options Options array
     * @return self
     */
    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * Get theme manager
     * 
     * @return ThemeManager
     */
    public function getThemeManager(): ThemeManager
    {
        return $this->themeManager;
    }

    /**
     * Render field label
     * 
     * @param FieldInterface $field Field instance
     * @param array $options Rendering options
     * @return string
     */
    public function renderLabel(FieldInterface $field, array $options = []): string
    {
        if (!($options['show_labels'] ?? true) || !$field->getLabel()) {
            return '';
        }

        $requiredMark = $field->isRequired() ? ' <span class="required">*</span>' : '';

        return sprintf(
            '<label for="%s">%s%s</label>',
            $field->getAttribute('id'),
            $this->escape($field->getLabel()),
            $requiredMark
        );
    }

    /**
     * Render help text
     * 
     * @param FieldInterface $field Field instance
     * @param array $options Rendering options
     * @return string
     */
    public function renderHelpText(FieldInterface $field, array $options = []): string
    {
        if (!($options['show_help_text'] ?? true) || !$field->getHelpText()) {
            return '';
        }

        return sprintf(
            '<small class="help-text">%s</small>',
            $this->escape($field->getHelpText())
        );
    }
}
