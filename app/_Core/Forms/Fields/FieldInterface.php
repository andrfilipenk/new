<?php
/**
 * Field Interface
 * 
 * Defines the contract for all form field types in the enhanced form system.
 * Each field must implement methods for value management, validation, 
 * rendering, and attribute handling.
 * 
 * @package Core\Forms\Fields
 * @since 2.0.0
 */

namespace Core\Forms\Fields;

use Core\Forms\Validation\ValidationResult;

interface FieldInterface
{
    /**
     * Get the field name
     * 
     * @return string The unique field name/identifier
     */
    public function getName(): string;

    /**
     * Get the field type
     * 
     * @return string The field type (e.g., 'text', 'email', 'select')
     */
    public function getType(): string;

    /**
     * Get the current field value
     * 
     * @return mixed The field value
     */
    public function getValue(): mixed;

    /**
     * Set the field value
     * 
     * @param mixed $value The value to set
     * @return self For method chaining
     */
    public function setValue(mixed $value): self;

    /**
     * Get all field HTML attributes
     * 
     * @return array Associative array of attribute name => value pairs
     */
    public function getAttributes(): array;

    /**
     * Set field HTML attributes
     * 
     * @param array $attributes Associative array of attributes
     * @return self For method chaining
     */
    public function setAttributes(array $attributes): self;

    /**
     * Set a single HTML attribute
     * 
     * @param string $name Attribute name
     * @param mixed $value Attribute value
     * @return self For method chaining
     */
    public function setAttribute(string $name, mixed $value): self;

    /**
     * Get a single HTML attribute
     * 
     * @param string $name Attribute name
     * @param mixed $default Default value if attribute not set
     * @return mixed The attribute value or default
     */
    public function getAttribute(string $name, mixed $default = null): mixed;

    /**
     * Get all validation rules for this field
     * 
     * @return array Array of validation rule configurations
     */
    public function getValidationRules(): array;

    /**
     * Add a validation rule to this field
     * 
     * @param string $ruleName The validation rule name
     * @param mixed $ruleConfig Rule configuration (parameters, message, etc.)
     * @return self For method chaining
     */
    public function addValidationRule(string $ruleName, mixed $ruleConfig = []): self;

    /**
     * Render the field as HTML
     * 
     * @param array $context Additional rendering context (errors, theme, etc.)
     * @return string The rendered HTML
     */
    public function render(array $context = []): string;

    /**
     * Validate the current field value
     * 
     * @param mixed $value The value to validate (defaults to current value)
     * @return ValidationResult The validation result
     */
    public function validate(mixed $value = null): ValidationResult;

    /**
     * Check if this field is required
     * 
     * @return bool True if field is required
     */
    public function isRequired(): bool;

    /**
     * Set whether this field is required
     * 
     * @param bool $required Required flag
     * @return self For method chaining
     */
    public function setRequired(bool $required = true): self;

    /**
     * Get the field label
     * 
     * @return string|null The field label
     */
    public function getLabel(): ?string;

    /**
     * Set the field label
     * 
     * @param string $label The label text
     * @return self For method chaining
     */
    public function setLabel(string $label): self;

    /**
     * Get help text for this field
     * 
     * @return string|null The help text
     */
    public function getHelpText(): ?string;

    /**
     * Set help text for this field
     * 
     * @param string $helpText The help text
     * @return self For method chaining
     */
    public function setHelpText(string $helpText): self;

    /**
     * Get the field's placeholder text
     * 
     * @return string|null The placeholder text
     */
    public function getPlaceholder(): ?string;

    /**
     * Set the field's placeholder text
     * 
     * @param string $placeholder The placeholder text
     * @return self For method chaining
     */
    public function setPlaceholder(string $placeholder): self;
}
