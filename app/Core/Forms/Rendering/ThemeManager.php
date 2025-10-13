<?php
/**
 * ThemeManager Class
 * 
 * Manages rendering themes for forms, providing support for
 * different CSS frameworks and custom styling approaches.
 * 
 * @package Core\Forms\Rendering
 * @since 2.0.0
 */

namespace Core\Forms\Rendering;

use Core\Forms\Fields\FieldInterface;

class ThemeManager
{
    /**
     * @var array Registered themes
     */
    private static array $themes = [];

    /**
     * @var string Default theme
     */
    private string $defaultTheme = 'default';

    /**
     * Initialize theme manager
     */
    public function __construct()
    {
        // Register built-in themes
        $this->registerBuiltInThemes();
    }

    /**
     * Register built-in themes
     */
    private function registerBuiltInThemes(): void
    {
        // Default theme
        self::registerTheme('default', new DefaultTheme());
        
        // Bootstrap theme
        self::registerTheme('bootstrap', new BootstrapTheme());
    }

    /**
     * Register a theme
     * 
     * @param string $name Theme name
     * @param ThemeInterface $theme Theme instance
     * @return void
     */
    public static function registerTheme(string $name, ThemeInterface $theme): void
    {
        self::$themes[$name] = $theme;
    }

    /**
     * Get a theme
     * 
     * @param string $name Theme name
     * @return ThemeInterface
     * @throws \InvalidArgumentException If theme not found
     */
    public function getTheme(string $name): ThemeInterface
    {
        if (!isset(self::$themes[$name])) {
            throw new \InvalidArgumentException("Theme '{$name}' not found");
        }

        return self::$themes[$name];
    }

    /**
     * Check if theme exists
     * 
     * @param string $name Theme name
     * @return bool
     */
    public function hasTheme(string $name): bool
    {
        return isset(self::$themes[$name]);
    }

    /**
     * Get all registered themes
     * 
     * @return array
     */
    public function getRegisteredThemes(): array
    {
        return array_keys(self::$themes);
    }

    /**
     * Set default theme
     * 
     * @param string $name Theme name
     * @return self
     */
    public function setDefaultTheme(string $name): self
    {
        $this->defaultTheme = $name;
        return $this;
    }

    /**
     * Get default theme
     * 
     * @return string
     */
    public function getDefaultTheme(): string
    {
        return $this->defaultTheme;
    }
}

/**
 * ThemeInterface
 * 
 * Interface for form rendering themes
 */
interface ThemeInterface
{
    /**
     * Render a field
     * 
     * @param FieldInterface $field Field to render
     * @param array $context Rendering context
     * @param array $options Rendering options
     * @return string Rendered HTML
     */
    public function renderField(FieldInterface $field, array $context, array $options): string;

    /**
     * Get theme CSS classes
     * 
     * @return array
     */
    public function getClasses(): array;
}

/**
 * DefaultTheme
 * 
 * Default clean theme without framework dependencies
 */
class DefaultTheme implements ThemeInterface
{
    /**
     * {@inheritdoc}
     */
    public function renderField(FieldInterface $field, array $context, array $options): string
    {
        $html = [];
        $fieldName = $field->getName();
        $fieldType = $field->getType();
        $hasError = isset($context['errors'][$fieldName]);

        // Field wrapper
        $wrapperClasses = ['form-field', "form-field-{$fieldType}"];
        if ($hasError) {
            $wrapperClasses[] = 'has-error';
        }

        $html[] = sprintf('<div class="%s">', implode(' ', $wrapperClasses));

        // Label
        if ($field->getLabel() && ($options['show_labels'] ?? true)) {
            $requiredMark = $field->isRequired() ? ' <span class="required">*</span>' : '';
            $html[] = sprintf(
                '<label for="%s">%s%s</label>',
                $field->getAttribute('id'),
                htmlspecialchars($field->getLabel()),
                $requiredMark
            );
        }

        // Field input
        $html[] = $this->renderInput($field);

        // Help text
        if ($field->getHelpText() && ($options['show_help_text'] ?? true)) {
            $html[] = sprintf(
                '<small class="help-text">%s</small>',
                htmlspecialchars($field->getHelpText())
            );
        }

        // Errors
        if ($hasError && ($options['show_errors'] ?? true)) {
            $html[] = '<div class="field-errors">';
            foreach ($context['errors'][$fieldName] as $error) {
                $html[] = sprintf('<span class="error">%s</span>', htmlspecialchars($error));
            }
            $html[] = '</div>';
        }

        $html[] = '</div>';

        return implode("\n", $html);
    }

    /**
     * Render field input element
     * 
     * @param FieldInterface $field Field instance
     * @return string
     */
    private function renderInput(FieldInterface $field): string
    {
        $type = $field->getType();
        $value = $field->getValue();
        $attrs = $this->buildAttributesString($field->getAttributes());

        return match($type) {
            'textarea' => sprintf('<textarea %s>%s</textarea>', $attrs, htmlspecialchars($value ?? '')),
            'select' => $field->render([]), // Delegate to field's own render
            default => sprintf('<input type="%s" %s value="%s">', $type, $attrs, htmlspecialchars($value ?? ''))
        };
    }

    /**
     * Build attributes string
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
                $parts[] = htmlspecialchars($name);
            } else {
                $parts[] = sprintf('%s="%s"', htmlspecialchars($name), htmlspecialchars($value));
            }
        }
        return implode(' ', $parts);
    }

    /**
     * {@inheritdoc}
     */
    public function getClasses(): array
    {
        return [
            'form' => 'form',
            'field' => 'form-field',
            'label' => 'form-label',
            'input' => 'form-input',
            'error' => 'error',
            'help' => 'help-text'
        ];
    }
}

/**
 * BootstrapTheme
 * 
 * Bootstrap 5 compatible theme
 */
class BootstrapTheme implements ThemeInterface
{
    /**
     * {@inheritdoc}
     */
    public function renderField(FieldInterface $field, array $context, array $options): string
    {
        $html = [];
        $fieldName = $field->getName();
        $fieldType = $field->getType();
        $hasError = isset($context['errors'][$fieldName]);

        // Field wrapper with Bootstrap classes
        $html[] = '<div class="mb-3">';

        // Label
        if ($field->getLabel() && ($options['show_labels'] ?? true)) {
            $requiredMark = $field->isRequired() ? ' <span class="text-danger">*</span>' : '';
            $html[] = sprintf(
                '<label for="%s" class="form-label">%s%s</label>',
                $field->getAttribute('id'),
                htmlspecialchars($field->getLabel()),
                $requiredMark
            );
        }

        // Field input with Bootstrap classes
        $html[] = $this->renderBootstrapInput($field, $hasError);

        // Help text
        if ($field->getHelpText() && ($options['show_help_text'] ?? true)) {
            $html[] = sprintf(
                '<div class="form-text">%s</div>',
                htmlspecialchars($field->getHelpText())
            );
        }

        // Errors
        if ($hasError && ($options['show_errors'] ?? true)) {
            foreach ($context['errors'][$fieldName] as $error) {
                $html[] = sprintf('<div class="invalid-feedback d-block">%s</div>', htmlspecialchars($error));
            }
        }

        $html[] = '</div>';

        return implode("\n", $html);
    }

    /**
     * Render Bootstrap-styled input
     * 
     * @param FieldInterface $field Field instance
     * @param bool $hasError Whether field has errors
     * @return string
     */
    private function renderBootstrapInput(FieldInterface $field, bool $hasError): string
    {
        $type = $field->getType();
        $value = $field->getValue();
        
        // Add Bootstrap classes
        $attributes = $field->getAttributes();
        $existingClass = $attributes['class'] ?? '';
        
        if ($type === 'select') {
            $attributes['class'] = trim($existingClass . ' form-select' . ($hasError ? ' is-invalid' : ''));
        } elseif ($type === 'textarea') {
            $attributes['class'] = trim($existingClass . ' form-control' . ($hasError ? ' is-invalid' : ''));
        } else {
            $attributes['class'] = trim($existingClass . ' form-control' . ($hasError ? ' is-invalid' : ''));
        }

        $attrs = $this->buildAttributesString($attributes);

        return match($type) {
            'textarea' => sprintf('<textarea %s>%s</textarea>', $attrs, htmlspecialchars($value ?? '')),
            'select' => $field->render([]), // Delegate to field
            default => sprintf('<input type="%s" %s value="%s">', $type, $attrs, htmlspecialchars($value ?? ''))
        };
    }

    /**
     * Build attributes string
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
                $parts[] = htmlspecialchars($name);
            } else {
                $parts[] = sprintf('%s="%s"', htmlspecialchars($name), htmlspecialchars($value));
            }
        }
        return implode(' ', $parts);
    }

    /**
     * {@inheritdoc}
     */
    public function getClasses(): array
    {
        return [
            'form' => 'needs-validation',
            'field' => 'mb-3',
            'label' => 'form-label',
            'input' => 'form-control',
            'select' => 'form-select',
            'error' => 'invalid-feedback',
            'help' => 'form-text'
        ];
    }
}
