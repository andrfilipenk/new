<?php
namespace Core\Forms\Events;

use Core\Forms\FormDefinition;

class FormEvent
{
    /**
     * Event types
     */
    public const EVENT_FORM_CREATED = 'form.created';
    public const EVENT_FORM_RENDERED = 'form.rendered';
    public const EVENT_FORM_SUBMITTED = 'form.submitted';
    public const EVENT_FORM_VALIDATED = 'form.validated';
    public const EVENT_FORM_VALIDATION_FAILED = 'form.validation_failed';
    public const EVENT_FORM_VALIDATION_PASSED = 'form.validation_passed';
    public const EVENT_FIELD_VALIDATED = 'field.validated';
    public const EVENT_FIELD_VALUE_CHANGED = 'field.value_changed';

    /**
     * @var string Event name
     */
    private string $eventName;

    /**
     * @var FormDefinition Form instance
     */
    private FormDefinition $form;

    /**
     * @var array Event data
     */
    private array $data;

    /**
     * @var float Event timestamp
     */
    private float $timestamp;

    /**
     * @var bool Whether event propagation has been stopped
     */
    private bool $propagationStopped = false;

    /**
     * @var bool Whether default action should be prevented
     */
    private bool $defaultPrevented = false;

    /**
     * Create a new form event
     * 
     * @param string $eventName Event name
     * @param FormDefinition $form Form instance
     * @param array $data Event-specific data
     */
    public function __construct(string $eventName, FormDefinition $form, array $data = [])
    {
        $this->eventName = $eventName;
        $this->form = $form;
        $this->data = $data;
        $this->timestamp = microtime(true);
    }

    /**
     * Get event name
     * 
     * @return string
     */
    public function getEventName(): string
    {
        return $this->eventName;
    }

    /**
     * Get form instance
     * 
     * @return FormDefinition
     */
    public function getForm(): FormDefinition
    {
        return $this->form;
    }

    /**
     * Get all event data
     * 
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get specific event data
     * 
     * @param string $key Data key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set event data
     * 
     * @param string $key Data key
     * @param mixed $value Data value
     * @return self
     */
    public function set(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Check if event data exists
     * 
     * @param string $key Data key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Get event timestamp
     * 
     * @return float
     */
    public function getTimestamp(): float
    {
        return $this->timestamp;
    }

    /**
     * Stop event propagation
     * 
     * @return self
     */
    public function stopPropagation(): self
    {
        $this->propagationStopped = true;
        return $this;
    }

    /**
     * Check if propagation is stopped
     * 
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Prevent default action
     * 
     * @return self
     */
    public function preventDefault(): self
    {
        $this->defaultPrevented = true;
        return $this;
    }

    /**
     * Check if default is prevented
     * 
     * @return bool
     */
    public function isDefaultPrevented(): bool
    {
        return $this->defaultPrevented;
    }

    /**
     * Get form name
     * 
     * @return string
     */
    public function getFormName(): string
    {
        return $this->form->getName();
    }

    /**
     * Get form data
     * 
     * @return array
     */
    public function getFormData(): array
    {
        $data = [];
        foreach ($this->form->getFields() as $field) {
            $data[$field->getName()] = $field->getValue();
        }
        return $data;
    }

    /**
     * Create a form created event
     * 
     * @param FormDefinition $form Form instance
     * @param array $data Additional data
     * @return self
     */
    public static function formCreated(FormDefinition $form, array $data = []): self
    {
        return new self(self::EVENT_FORM_CREATED, $form, $data);
    }

    /**
     * Create a form rendered event
     * 
     * @param FormDefinition $form Form instance
     * @param string $output Rendered HTML
     * @param array $data Additional data
     * @return self
     */
    public static function formRendered(FormDefinition $form, string $output, array $data = []): self
    {
        $data['output'] = $output;
        return new self(self::EVENT_FORM_RENDERED, $form, $data);
    }

    /**
     * Create a form submitted event
     * 
     * @param FormDefinition $form Form instance
     * @param array $submittedData Submitted data
     * @return self
     */
    public static function formSubmitted(FormDefinition $form, array $submittedData): self
    {
        return new self(self::EVENT_FORM_SUBMITTED, $form, [
            'submitted_data' => $submittedData
        ]);
    }

    /**
     * Create a form validated event
     * 
     * @param FormDefinition $form Form instance
     * @param bool $isValid Whether validation passed
     * @param array $errors Validation errors
     * @return self
     */
    public static function formValidated(FormDefinition $form, bool $isValid, array $errors = []): self
    {
        return new self(self::EVENT_FORM_VALIDATED, $form, [
            'is_valid' => $isValid,
            'errors' => $errors
        ]);
    }

    /**
     * Create a validation failed event
     * 
     * @param FormDefinition $form Form instance
     * @param array $errors Validation errors
     * @return self
     */
    public static function validationFailed(FormDefinition $form, array $errors): self
    {
        return new self(self::EVENT_FORM_VALIDATION_FAILED, $form, [
            'errors' => $errors
        ]);
    }

    /**
     * Create a validation passed event
     * 
     * @param FormDefinition $form Form instance
     * @param array $validatedData Validated data
     * @return self
     */
    public static function validationPassed(FormDefinition $form, array $validatedData): self
    {
        return new self(self::EVENT_FORM_VALIDATION_PASSED, $form, [
            'validated_data' => $validatedData
        ]);
    }
}
