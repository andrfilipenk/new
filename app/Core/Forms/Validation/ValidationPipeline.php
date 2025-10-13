<?php
/**
 * ValidationPipeline Class
 * 
 * Orchestrates the complete validation process by coordinating
 * field validators, form validators, and error aggregation.
 * 
 * @package Core\Forms\Validation
 * @since 2.0.0
 */

namespace Core\Forms\Validation;

use Core\Forms\FormDefinition;
use Core\Forms\Fields\FieldInterface;

class ValidationPipeline
{
    /**
     * @var FieldValidator Field validator instance
     */
    private FieldValidator $fieldValidator;

    /**
     * @var FormValidator Form validator instance
     */
    private FormValidator $formValidator;

    /**
     * @var ErrorAggregator Error aggregator instance
     */
    private ErrorAggregator $errorAggregator;

    /**
     * @var bool Whether to stop on first error
     */
    private bool $stopOnFirstError = false;

    /**
     * Create a new validation pipeline
     * 
     * @param FieldValidator|null $fieldValidator Custom field validator
     * @param FormValidator|null $formValidator Custom form validator
     */
    public function __construct(?FieldValidator $fieldValidator = null, ?FormValidator $formValidator = null)
    {
        $this->fieldValidator = $fieldValidator ?? new FieldValidator();
        $this->formValidator = $formValidator ?? new FormValidator();
        $this->errorAggregator = new ErrorAggregator();
    }

    /**
     * Validate a complete form
     * 
     * @param FormDefinition $form Form definition
     * @param array $data Form data to validate
     * @return ValidationResult
     */
    public function validate(FormDefinition $form, array $data): ValidationResult
    {
        $this->errorAggregator->clear();
        
        // Step 1: Validate individual fields
        $fieldResult = $this->validateFields($form, $data);
        
        if ($this->stopOnFirstError && $fieldResult->isFailed()) {
            return $fieldResult;
        }
        
        // Step 2: Validate form-level rules
        $formResult = $this->validateFormRules($form, $data);
        
        // Combine results
        return $this->errorAggregator->toValidationResult();
    }

    /**
     * Validate all form fields
     * 
     * @param FormDefinition $form Form definition
     * @param array $data Form data
     * @return ValidationResult
     */
    private function validateFields(FormDefinition $form, array $data): ValidationResult
    {
        $fields = $form->getFields();
        
        foreach ($fields as $field) {
            $fieldName = $field->getName();
            $value = $data[$fieldName] ?? null;
            
            // Validate the field
            $result = $this->fieldValidator->validate($field, $value, $data);
            
            if ($result->isFailed()) {
                $this->errorAggregator->addResult($result);
                
                if ($this->stopOnFirstError) {
                    return $this->errorAggregator->toValidationResult();
                }
            }
        }
        
        return $this->errorAggregator->toValidationResult();
    }

    /**
     * Validate form-level rules
     * 
     * @param FormDefinition $form Form definition
     * @param array $data Form data
     * @return ValidationResult
     */
    private function validateFormRules(FormDefinition $form, array $data): ValidationResult
    {
        $result = $this->formValidator->validate($form, $data);
        
        if ($result->isFailed()) {
            $this->errorAggregator->addResult($result);
        }
        
        return $result;
    }

    /**
     * Validate a single field
     * 
     * @param FieldInterface $field Field to validate
     * @param mixed $value Value to validate
     * @param array $context Validation context (other field values)
     * @return ValidationResult
     */
    public function validateField(FieldInterface $field, mixed $value, array $context = []): ValidationResult
    {
        return $this->fieldValidator->validate($field, $value, $context);
    }

    /**
     * Set whether to stop validation on first error
     * 
     * @param bool $stop Stop on first error flag
     * @return self
     */
    public function setStopOnFirstError(bool $stop): self
    {
        $this->stopOnFirstError = $stop;
        return $this;
    }

    /**
     * Get the error aggregator
     * 
     * @return ErrorAggregator
     */
    public function getErrorAggregator(): ErrorAggregator
    {
        return $this->errorAggregator;
    }

    /**
     * Get the field validator
     * 
     * @return FieldValidator
     */
    public function getFieldValidator(): FieldValidator
    {
        return $this->fieldValidator;
    }

    /**
     * Get the form validator
     * 
     * @return FormValidator
     */
    public function getFormValidator(): FormValidator
    {
        return $this->formValidator;
    }

    /**
     * Quick validation helper
     * 
     * @param FormDefinition $form Form to validate
     * @param array $data Data to validate
     * @return bool True if validation passed
     */
    public static function isValid(FormDefinition $form, array $data): bool
    {
        $pipeline = new self();
        $result = $pipeline->validate($form, $data);
        return $result->isValid();
    }

    /**
     * Quick validation with error retrieval
     * 
     * @param FormDefinition $form Form to validate
     * @param array $data Data to validate
     * @return array Validation result array with 'isValid' and 'errors'
     */
    public static function validateWithErrors(FormDefinition $form, array $data): array
    {
        $pipeline = new self();
        $result = $pipeline->validate($form, $data);
        
        return [
            'isValid' => $result->isValid(),
            'errors' => $result->getErrors(),
            'hasErrors' => $result->isFailed()
        ];
    }
}
