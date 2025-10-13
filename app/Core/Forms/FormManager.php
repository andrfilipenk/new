<?php
/**
 * FormManager Class
 * 
 * Orchestrates the complete form lifecycle including creation,
 * validation, submission handling, and data binding.
 * 
 * @package Core\Forms
 * @since 2.0.0
 */

namespace Core\Forms;

use Core\Forms\Validation\ValidationPipeline;
use Core\Forms\Validation\ValidationResult;
use Core\Forms\Security\SecurityManager;

class FormManager
{
    /**
     * @var FormDefinition Form definition
     */
    private FormDefinition $form;

    /**
     * @var ValidationPipeline Validation pipeline
     */
    private ValidationPipeline $validationPipeline;

    /**
     * @var SecurityManager Security manager
     */
    private SecurityManager $securityManager;

    /**
     * @var ValidationResult|null Last validation result
     */
    private ?ValidationResult $validationResult = null;

    /**
     * @var array Validated data
     */
    private array $validatedData = [];

    /**
     * @var bool Whether form has been submitted
     */
    private bool $isSubmitted = false;

    /**
     * @var array Raw request data
     */
    private array $requestData = [];

    /**
     * Create a new form manager
     * 
     * @param FormDefinition $form Form definition
     * @param ValidationPipeline|null $validationPipeline Custom validation pipeline
     * @param SecurityManager|null $securityManager Custom security manager
     */
    public function __construct(
        FormDefinition $form,
        ?ValidationPipeline $validationPipeline = null,
        ?SecurityManager $securityManager = null
    ) {
        $this->form = $form;
        $this->validationPipeline = $validationPipeline ?? new ValidationPipeline();
        $this->securityManager = $securityManager ?? SecurityManager::createForForm($form);
    }

    /**
     * Handle form request
     * 
     * @param mixed $request Request object or array
     * @return self For method chaining
     */
    public function handleRequest(mixed $request): self
    {
        // Extract data from request
        $this->requestData = $this->extractRequestData($request);
        $this->isSubmitted = !empty($this->requestData);
        
        if (!$this->isSubmitted) {
            return $this;
        }
        
        // Process security
        $securityResult = $this->securityManager->processSubmission($this->form, $this->requestData);
        
        if (!$securityResult['valid']) {
            $this->validationResult = ValidationResult::failure([
                '_form' => [$securityResult['message'] ?? 'Security validation failed']
            ]);
            return $this;
        }
        
        $sanitizedData = $securityResult['data'];
        
        // Populate form fields with data
        $this->populateFields($sanitizedData);
        
        // Validate form
        $this->validationResult = $this->validationPipeline->validate($this->form, $sanitizedData);
        
        if ($this->validationResult->isValid()) {
            $this->validatedData = $this->getFieldValues();
        }
        
        return $this;
    }

    /**
     * Extract request data
     * 
     * @param mixed $request Request object or array
     * @return array Request data
     */
    private function extractRequestData(mixed $request): array
    {
        if (is_array($request)) {
            return $request;
        }
        
        // Handle Core\Http\Request object
        if (is_object($request) && method_exists($request, 'getPost')) {
            return $request->getPost();
        }
        
        if (is_object($request) && method_exists($request, 'all')) {
            return $request->all();
        }
        
        // Fallback to $_POST
        return $_POST ?? [];
    }

    /**
     * Populate form fields with data
     * 
     * @param array $data Data to populate
     * @return self
     */
    public function populateFields(array $data): self
    {
        $this->form->getFields()->setValues($data);
        return $this;
    }

    /**
     * Get field values from form
     * 
     * @return array
     */
    private function getFieldValues(): array
    {
        return $this->form->getFields()->getValues();
    }

    /**
     * Check if form is valid
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->validationResult !== null && $this->validationResult->isValid();
    }

    /**
     * Check if form has been submitted
     * 
     * @return bool
     */
    public function isSubmitted(): bool
    {
        return $this->isSubmitted;
    }

    /**
     * Get validated data
     * 
     * @return array
     */
    public function getValidatedData(): array
    {
        return $this->validatedData;
    }

    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors(): array
    {
        return $this->validationResult?->getErrors() ?? [];
    }

    /**
     * Get validation result
     * 
     * @return ValidationResult|null
     */
    public function getValidationResult(): ?ValidationResult
    {
        return $this->validationResult;
    }

    /**
     * Get form definition
     * 
     * @return FormDefinition
     */
    public function getForm(): FormDefinition
    {
        return $this->form;
    }

    /**
     * Bind data to form (set values without validation)
     * 
     * @param array $data Data to bind
     * @return self
     */
    public function bind(array $data): self
    {
        $this->populateFields($data);
        return $this;
    }

    /**
     * Validate form without handling request
     * 
     * @param array $data Data to validate
     * @return self
     */
    public function validate(array $data): self
    {
        $this->requestData = $data;
        $this->isSubmitted = true;
        
        // Apply security
        $sanitizedData = $this->securityManager->sanitizeInput($data, $this->getFieldTypes());
        
        // Populate and validate
        $this->populateFields($sanitizedData);
        $this->validationResult = $this->validationPipeline->validate($this->form, $sanitizedData);
        
        if ($this->validationResult->isValid()) {
            $this->validatedData = $this->getFieldValues();
        }
        
        return $this;
    }

    /**
     * Get field types
     * 
     * @return array
     */
    private function getFieldTypes(): array
    {
        $types = [];
        foreach ($this->form->getFields() as $field) {
            $types[$field->getName()] = $field->getType();
        }
        return $types;
    }

    /**
     * Get CSRF field HTML
     * 
     * @return string
     */
    public function getCsrfField(): string
    {
        return $this->securityManager->getCsrfField($this->form->getName());
    }

    /**
     * Render the form
     * 
     * @param array $options Rendering options
     * @return string Rendered HTML
     */
    public function render(array $options = []): string
    {
        $html = [];
        
        // Form opening tag
        $attributes = $this->form->getAttributes();
        $attrString = $this->buildAttributesString($attributes);
        $html[] = "<form {$attrString}>";
        
        // CSRF field
        if ($this->form->isCsrfEnabled()) {
            $html[] = $this->getCsrfField();
        }
        
        // Render fields
        $context = array_merge([
            'errors' => $this->getErrors(),
            'show_errors' => true,
            'show_help_text' => true,
            'show_labels' => true,
        ], $options);
        
        foreach ($this->form->getFields() as $field) {
            $html[] = $field->render($context);
        }
        
        // Submit button (if not disabled)
        if (!($options['hide_submit'] ?? false)) {
            $submitText = $options['submit_text'] ?? 'Submit';
            $submitClass = $options['submit_class'] ?? 'btn btn-primary';
            $html[] = sprintf(
                '<div class="form-submit"><button type="submit" class="%s">%s</button></div>',
                htmlspecialchars($submitClass),
                htmlspecialchars($submitText)
            );
        }
        
        // Form closing tag
        $html[] = '</form>';
        
        return implode("\n", $html);
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
                $parts[] = htmlspecialchars($name);
            } else {
                $parts[] = sprintf(
                    '%s="%s"',
                    htmlspecialchars($name),
                    htmlspecialchars($value)
                );
            }
        }
        return implode(' ', $parts);
    }

    /**
     * Get a specific field value
     * 
     * @param string $fieldName Field name
     * @param mixed $default Default value
     * @return mixed
     */
    public function getValue(string $fieldName, mixed $default = null): mixed
    {
        $field = $this->form->getField($fieldName);
        return $field ? $field->getValue() : $default;
    }

    /**
     * Set a specific field value
     * 
     * @param string $fieldName Field name
     * @param mixed $value Value to set
     * @return self
     */
    public function setValue(string $fieldName, mixed $value): self
    {
        $field = $this->form->getField($fieldName);
        if ($field) {
            $field->setValue($value);
        }
        return $this;
    }

    /**
     * Check if a specific field has errors
     * 
     * @param string $fieldName Field name
     * @return bool
     */
    public function hasFieldError(string $fieldName): bool
    {
        return $this->validationResult?->hasFieldErrors($fieldName) ?? false;
    }

    /**
     * Get errors for a specific field
     * 
     * @param string $fieldName Field name
     * @return array
     */
    public function getFieldErrors(string $fieldName): array
    {
        return $this->validationResult?->getFieldErrors($fieldName) ?? [];
    }

    /**
     * Get first error for a field
     * 
     * @param string $fieldName Field name
     * @return string|null
     */
    public function getFirstFieldError(string $fieldName): ?string
    {
        return $this->validationResult?->getFirstFieldError($fieldName);
    }

    /**
     * Reset the form
     * 
     * @return self
     */
    public function reset(): self
    {
        $this->isSubmitted = false;
        $this->validationResult = null;
        $this->validatedData = [];
        $this->requestData = [];
        
        // Clear field values
        foreach ($this->form->getFields() as $field) {
            $field->setValue(null);
        }
        
        return $this;
    }

    /**
     * Get security manager
     * 
     * @return SecurityManager
     */
    public function getSecurityManager(): SecurityManager
    {
        return $this->securityManager;
    }

    /**
     * Get validation pipeline
     * 
     * @return ValidationPipeline
     */
    public function getValidationPipeline(): ValidationPipeline
    {
        return $this->validationPipeline;
    }

    /**
     * Get raw request data
     * 
     * @return array
     */
    public function getRequestData(): array
    {
        return $this->requestData;
    }

    /**
     * Create form manager from form definition
     * 
     * @param FormDefinition $form Form definition
     * @return self
     */
    public static function create(FormDefinition $form): self
    {
        return new self($form);
    }
}
