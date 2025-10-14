<?php
namespace Core\Forms\Validation;

use Core\Forms\FormDefinition;

class FormValidator
{
    /**
     * @var array Custom form-level validators
     */
    private static array $customValidators = [];

    /**
     * Validate form-level rules
     * 
     * @param FormDefinition $form Form definition
     * @param array $data Form data
     * @return ValidationResult
     */
    public function validate(FormDefinition $form, array $data): ValidationResult
    {
        $result = ValidationResult::success();
        $rules = $form->getValidationRules();
        
        foreach ($rules as $ruleName => $ruleConfig) {
            $validationResult = $this->validateRule($ruleName, $ruleConfig, $data, $form);
            
            if ($validationResult->isFailed()) {
                $result->merge($validationResult);
            }
        }
        
        return $result;
    }

    /**
     * Validate a single form-level rule
     * 
     * @param string $ruleName Rule name
     * @param mixed $ruleConfig Rule configuration
     * @param array $data Form data
     * @param FormDefinition $form Form definition
     * @return ValidationResult
     */
    private function validateRule(string $ruleName, mixed $ruleConfig, array $data, FormDefinition $form): ValidationResult
    {
        // Check custom validators
        if (isset(self::$customValidators[$ruleName])) {
            $validator = self::$customValidators[$ruleName];
            return $validator($data, $ruleConfig, $form);
        }
        
        // Check built-in validators
        $methodName = 'validate' . ucfirst($ruleName);
        
        if (method_exists($this, $methodName)) {
            return $this->$methodName($data, $ruleConfig, $form);
        }
        
        return ValidationResult::success();
    }

    /**
     * Register a custom form-level validator
     * 
     * @param string $name Validator name
     * @param callable $callback Validator callback
     * @return void
     */
    public static function registerValidator(string $name, callable $callback): void
    {
        self::$customValidators[$name] = $callback;
    }

    // ==================== Built-in Form Validators ====================

    /**
     * Validate that fields match
     * 
     * Config: ['fields' => ['password', 'password_confirm'], 'message' => 'Passwords must match']
     */
    private function validateFieldsMatch(array $data, mixed $config, FormDefinition $form): ValidationResult
    {
        if (!is_array($config) || !isset($config['fields']) || count($config['fields']) < 2) {
            return ValidationResult::success();
        }
        
        $fields = $config['fields'];
        $firstValue = $data[$fields[0]] ?? null;
        
        foreach (array_slice($fields, 1) as $fieldName) {
            $value = $data[$fieldName] ?? null;
            
            if ($firstValue !== $value) {
                $message = $config['message'] ?? 'Fields must match.';
                return ValidationResult::failure([
                    $fieldName => [$message]
                ]);
            }
        }
        
        return ValidationResult::success();
    }

    /**
     * Validate that at least one field is filled
     * 
     * Config: ['fields' => ['phone', 'email'], 'message' => 'Provide phone or email']
     */
    private function validateRequireOneOf(array $data, mixed $config, FormDefinition $form): ValidationResult
    {
        if (!is_array($config) || !isset($config['fields'])) {
            return ValidationResult::success();
        }
        
        $fields = $config['fields'];
        $hasValue = false;
        
        foreach ($fields as $fieldName) {
            $value = $data[$fieldName] ?? null;
            if (!empty($value)) {
                $hasValue = true;
                break;
            }
        }
        
        if (!$hasValue) {
            $message = $config['message'] ?? 'At least one of these fields is required.';
            $firstField = $fields[0] ?? '_form';
            
            return ValidationResult::failure([
                $firstField => [$message]
            ]);
        }
        
        return ValidationResult::success();
    }

    /**
     * Validate conditional requirement
     * 
     * Config: [
     *   'if_field' => 'country',
     *   'equals' => 'US',
     *   'then_required' => ['state', 'zip_code']
     * ]
     */
    private function validateConditionalRequired(array $data, mixed $config, FormDefinition $form): ValidationResult
    {
        if (!is_array($config) || !isset($config['if_field'], $config['then_required'])) {
            return ValidationResult::success();
        }
        
        $conditionField = $config['if_field'];
        $conditionValue = $data[$conditionField] ?? null;
        $expectedValue = $config['equals'] ?? null;
        
        // Check if condition is met
        $conditionMet = false;
        
        if ($expectedValue !== null) {
            $conditionMet = $conditionValue == $expectedValue;
        } else {
            $conditionMet = !empty($conditionValue);
        }
        
        if (!$conditionMet) {
            return ValidationResult::success();
        }
        
        // Validate required fields
        $result = ValidationResult::success();
        $requiredFields = (array)$config['then_required'];
        
        foreach ($requiredFields as $fieldName) {
            $value = $data[$fieldName] ?? null;
            
            if (empty($value)) {
                $message = $config['message'] ?? "This field is required when {$conditionField} is {$expectedValue}.";
                $result->addError($fieldName, $message);
            }
        }
        
        return $result;
    }

    /**
     * Validate field combination uniqueness
     * 
     * Config: [
     *   'fields' => ['first_name', 'last_name'],
     *   'callback' => function($values) { return !existsInDb($values); }
     * ]
     */
    private function validateUniqueCombination(array $data, mixed $config, FormDefinition $form): ValidationResult
    {
        if (!is_array($config) || !isset($config['fields'], $config['callback'])) {
            return ValidationResult::success();
        }
        
        $fields = $config['fields'];
        $values = [];
        
        foreach ($fields as $fieldName) {
            $values[$fieldName] = $data[$fieldName] ?? null;
        }
        
        $callback = $config['callback'];
        $isUnique = $callback($values);
        
        if (!$isUnique) {
            $message = $config['message'] ?? 'This combination already exists.';
            $firstField = $fields[0] ?? '_form';
            
            return ValidationResult::failure([
                $firstField => [$message]
            ]);
        }
        
        return ValidationResult::success();
    }

    /**
     * Validate total sum
     * 
     * Config: [
     *   'fields' => ['amount1', 'amount2', 'amount3'],
     *   'equals' => 100,
     *   'message' => 'Total must equal 100'
     * ]
     */
    private function validateSum(array $data, mixed $config, FormDefinition $form): ValidationResult
    {
        if (!is_array($config) || !isset($config['fields'])) {
            return ValidationResult::success();
        }
        
        $fields = $config['fields'];
        $sum = 0;
        
        foreach ($fields as $fieldName) {
            $value = $data[$fieldName] ?? 0;
            $sum += (float)$value;
        }
        
        $expectedSum = $config['equals'] ?? null;
        
        if ($expectedSum !== null && $sum != $expectedSum) {
            $message = $config['message'] ?? "Total must equal {$expectedSum}.";
            $firstField = $fields[0] ?? '_form';
            
            return ValidationResult::failure([
                $firstField => [$message]
            ]);
        }
        
        return ValidationResult::success();
    }

    /**
     * Validate date range
     * 
     * Config: [
     *   'start_field' => 'start_date',
     *   'end_field' => 'end_date',
     *   'max_days' => 30
     * ]
     */
    private function validateDateRange(array $data, mixed $config, FormDefinition $form): ValidationResult
    {
        if (!is_array($config) || !isset($config['start_field'], $config['end_field'])) {
            return ValidationResult::success();
        }
        
        $startField = $config['start_field'];
        $endField = $config['end_field'];
        
        $startDate = $data[$startField] ?? null;
        $endDate = $data[$endField] ?? null;
        
        if (!$startDate || !$endDate) {
            return ValidationResult::success();
        }
        
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        
        if ($startTime === false || $endTime === false) {
            return ValidationResult::success();
        }
        
        // Validate end is after start
        if ($endTime <= $startTime) {
            $message = $config['message'] ?? 'End date must be after start date.';
            return ValidationResult::failure([
                $endField => [$message]
            ]);
        }
        
        // Validate max days if specified
        if (isset($config['max_days'])) {
            $maxDays = (int)$config['max_days'];
            $diffDays = ($endTime - $startTime) / (60 * 60 * 24);
            
            if ($diffDays > $maxDays) {
                $message = $config['range_message'] ?? "Date range cannot exceed {$maxDays} days.";
                return ValidationResult::failure([
                    $endField => [$message]
                ]);
            }
        }
        
        return ValidationResult::success();
    }
}
