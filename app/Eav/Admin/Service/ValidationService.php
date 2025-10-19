<?php

namespace Eav\Admin\Service;

use Eav\Repositories\EntityTypeRepository;
use Eav\Repositories\AttributeRepository;

class ValidationService
{
    private EntityTypeRepository $entityTypeRepo;
    private AttributeRepository $attributeRepo;
    
    public function __construct(
        EntityTypeRepository $entityTypeRepo,
        AttributeRepository $attributeRepo
    ) {
        $this->entityTypeRepo = $entityTypeRepo;
        $this->attributeRepo = $attributeRepo;
    }
    
    /**
     * Validate entity data against attribute definitions
     */
    public function validateEntityData(string $entityTypeCode, array $data, ?int $entityId = null): array
    {
        $errors = [];
        
        // Get entity type
        $entityType = $this->entityTypeRepo->findByCode($entityTypeCode);
        if (!$entityType) {
            return [
                'valid' => false,
                'errors' => [['field' => '_entity', 'message' => "Entity type '{$entityTypeCode}' not found"]]
            ];
        }
        
        // Get all attributes for entity type
        $attributes = $this->attributeRepo->getByEntityType($entityType->entity_type_id);
        $attributesByCode = [];
        foreach ($attributes as $attr) {
            $attributesByCode[$attr->attribute_code] = $attr;
        }
        
        // Validate each field
        foreach ($data as $attributeCode => $value) {
            if (!isset($attributesByCode[$attributeCode])) {
                $errors[] = [
                    'field' => $attributeCode,
                    'message' => "Unknown attribute '{$attributeCode}'"
                ];
                continue;
            }
            
            $attribute = $attributesByCode[$attributeCode];
            $fieldErrors = $this->validateAttribute($attribute, $value, $entityId);
            
            if (!empty($fieldErrors)) {
                $errors = array_merge($errors, $fieldErrors);
            }
        }
        
        // Check required attributes
        foreach ($attributesByCode as $code => $attribute) {
            if ($attribute->is_required && !isset($data[$code])) {
                $errors[] = [
                    'field' => $code,
                    'message' => "{$attribute->attribute_label} is required"
                ];
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate single attribute value
     */
    private function validateAttribute(object $attribute, $value, ?int $entityId = null): array
    {
        $errors = [];
        $code = $attribute->attribute_code;
        $label = $attribute->attribute_label;
        
        // Required check
        if ($attribute->is_required && ($value === null || $value === '')) {
            $errors[] = [
                'field' => $code,
                'message' => "{$label} is required"
            ];
            return $errors; // Stop further validation if required and empty
        }
        
        // Skip other validations if value is empty and not required
        if ($value === null || $value === '') {
            return $errors;
        }
        
        // Type validation
        switch ($attribute->backend_type) {
            case 'int':
                if (!is_numeric($value) || (int)$value != $value) {
                    $errors[] = [
                        'field' => $code,
                        'message' => "{$label} must be an integer"
                    ];
                }
                break;
                
            case 'decimal':
                if (!is_numeric($value)) {
                    $errors[] = [
                        'field' => $code,
                        'message' => "{$label} must be a number"
                    ];
                }
                break;
                
            case 'datetime':
                if (!strtotime($value)) {
                    $errors[] = [
                        'field' => $code,
                        'message' => "{$label} must be a valid date/time"
                    ];
                }
                break;
        }
        
        // Validation rules
        if ($attribute->validation_rules) {
            $rules = is_string($attribute->validation_rules) 
                ? json_decode($attribute->validation_rules, true) 
                : $attribute->validation_rules;
            
            foreach ($rules as $rule) {
                $ruleType = is_array($rule) ? ($rule['rule'] ?? '') : $rule;
                
                switch ($ruleType) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[] = [
                                'field' => $code,
                                'message' => $rule['message'] ?? "{$label} must be a valid email address"
                            ];
                        }
                        break;
                        
                    case 'url':
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            $errors[] = [
                                'field' => $code,
                                'message' => $rule['message'] ?? "{$label} must be a valid URL"
                            ];
                        }
                        break;
                        
                    case 'min':
                        $min = $rule['value'] ?? 0;
                        if (is_numeric($value) && $value < $min) {
                            $errors[] = [
                                'field' => $code,
                                'message' => $rule['message'] ?? "{$label} must be at least {$min}"
                            ];
                        } elseif (is_string($value) && strlen($value) < $min) {
                            $errors[] = [
                                'field' => $code,
                                'message' => $rule['message'] ?? "{$label} must be at least {$min} characters"
                            ];
                        }
                        break;
                        
                    case 'max':
                        $max = $rule['value'] ?? PHP_INT_MAX;
                        if (is_numeric($value) && $value > $max) {
                            $errors[] = [
                                'field' => $code,
                                'message' => $rule['message'] ?? "{$label} must be no more than {$max}"
                            ];
                        } elseif (is_string($value) && strlen($value) > $max) {
                            $errors[] = [
                                'field' => $code,
                                'message' => $rule['message'] ?? "{$label} must be no more than {$max} characters"
                            ];
                        }
                        break;
                        
                    case 'regex':
                        $pattern = $rule['pattern'] ?? '';
                        if ($pattern && !preg_match($pattern, $value)) {
                            $errors[] = [
                                'field' => $code,
                                'message' => $rule['message'] ?? "{$label} format is invalid"
                            ];
                        }
                        break;
                        
                    case 'unique':
                        if ($this->isValueDuplicate($attribute, $value, $entityId)) {
                            $errors[] = [
                                'field' => $code,
                                'message' => $rule['message'] ?? "{$label} must be unique"
                            ];
                        }
                        break;
                }
            }
        }
        
        // Options validation for select/multiselect
        if (in_array($attribute->frontend_input, ['select', 'multiselect']) && $attribute->options) {
            $options = is_string($attribute->options) 
                ? json_decode($attribute->options, true) 
                : $attribute->options;
            
            $validValues = array_column($options, 'value');
            
            if ($attribute->frontend_input === 'multiselect') {
                $values = is_array($value) ? $value : explode(',', $value);
                foreach ($values as $v) {
                    if (!in_array($v, $validValues)) {
                        $errors[] = [
                            'field' => $code,
                            'message' => "Invalid option value '{$v}' for {$label}"
                        ];
                    }
                }
            } else {
                if (!in_array($value, $validValues)) {
                    $errors[] = [
                        'field' => $code,
                        'message' => "Invalid option value for {$label}"
                    ];
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Check if value is duplicate for unique attributes
     */
    private function isValueDuplicate(object $attribute, $value, ?int $excludeEntityId = null): bool
    {
        $entityType = $this->entityTypeRepo->find($attribute->entity_type_id);
        
        if ($entityType->storage_strategy === 'flat') {
            // Flat table
            $query = \Core\Database\DB::table($entityType->entity_table)
                ->where($attribute->attribute_code, $value);
            
            if ($excludeEntityId) {
                $query->where('entity_id', '!=', $excludeEntityId);
            }
            
            return $query->exists();
        } else {
            // EAV storage
            $valueTable = 'eav_entity_' . $attribute->backend_type;
            
            $query = \Core\Database\DB::table($valueTable)
                ->where('attribute_id', $attribute->attribute_id)
                ->where('value', $value);
            
            if ($excludeEntityId) {
                $query->where('entity_id', '!=', $excludeEntityId);
            }
            
            return $query->exists();
        }
    }
}
