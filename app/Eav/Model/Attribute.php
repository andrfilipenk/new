<?php
// app/Eav/Model/Attribute.php
namespace Eav\Model;

use Eav\Exception\ValidationException;
use Eav\Exception\ConfigurationException;

/**
 * Attribute Model
 * 
 * Represents a single attribute definition in the EAV system.
 */
class Attribute
{
    /**
     * Attribute ID (from database)
     */
    protected ?int $attributeId = null;

    /**
     * Entity type ID this attribute belongs to
     */
    protected int $entityTypeId;

    /**
     * Attribute code (unique identifier)
     */
    protected string $attributeCode;

    /**
     * Human-readable attribute label
     */
    protected string $attributeLabel;

    /**
     * Backend storage type (varchar, int, decimal, datetime, text)
     */
    protected string $backendType;

    /**
     * Frontend input type (text, select, multiselect, date, boolean)
     */
    protected string $frontendType;

    /**
     * Is this attribute required?
     */
    protected bool $isRequired = false;

    /**
     * Must values be unique?
     */
    protected bool $isUnique = false;

    /**
     * Is this attribute searchable?
     */
    protected bool $isSearchable = false;

    /**
     * Is this attribute filterable?
     */
    protected bool $isFilterable = false;

    /**
     * Is this attribute comparable?
     */
    protected bool $isComparable = false;

    /**
     * Default value for this attribute
     */
    protected mixed $defaultValue = null;

    /**
     * Validation rules
     */
    protected array $validationRules = [];

    /**
     * Source model for select/multiselect types
     */
    protected ?string $sourceModel = null;

    /**
     * Backend model for custom processing
     */
    protected ?string $backendModel = null;

    /**
     * Frontend model for custom rendering
     */
    protected ?string $frontendModel = null;

    /**
     * Sort order
     */
    protected int $sortOrder = 0;

    /**
     * Valid backend types
     */
    protected const BACKEND_TYPES = ['varchar', 'int', 'decimal', 'datetime', 'text'];

    /**
     * Valid frontend types
     */
    protected const FRONTEND_TYPES = ['text', 'textarea', 'select', 'multiselect', 'date', 'datetime', 'boolean', 'number'];

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        if (isset($config['attribute_id'])) {
            $this->attributeId = (int)$config['attribute_id'];
        }
        if (isset($config['entity_type_id'])) {
            $this->entityTypeId = (int)$config['entity_type_id'];
        }
        if (isset($config['attribute_code'])) {
            $this->attributeCode = $config['attribute_code'];
        }
        if (isset($config['attribute_label'])) {
            $this->attributeLabel = $config['attribute_label'];
        }
        if (isset($config['backend_type'])) {
            $this->setBackendType($config['backend_type']);
        }
        if (isset($config['frontend_type'])) {
            $this->setFrontendType($config['frontend_type']);
        }
        
        $this->isRequired = $config['is_required'] ?? false;
        $this->isUnique = $config['is_unique'] ?? false;
        $this->isSearchable = $config['is_searchable'] ?? false;
        $this->isFilterable = $config['is_filterable'] ?? false;
        $this->isComparable = $config['is_comparable'] ?? false;
        $this->defaultValue = $config['default_value'] ?? null;
        $this->validationRules = $config['validation_rules'] ?? [];
        $this->sourceModel = $config['source_model'] ?? null;
        $this->backendModel = $config['backend_model'] ?? null;
        $this->frontendModel = $config['frontend_model'] ?? null;
        $this->sortOrder = $config['sort_order'] ?? 0;
    }

    /**
     * Get attribute ID
     */
    public function getAttributeId(): ?int
    {
        return $this->attributeId;
    }

    /**
     * Set attribute ID
     */
    public function setAttributeId(int $id): self
    {
        $this->attributeId = $id;
        return $this;
    }

    /**
     * Get entity type ID
     */
    public function getEntityTypeId(): int
    {
        return $this->entityTypeId;
    }

    /**
     * Get attribute code
     */
    public function getAttributeCode(): string
    {
        return $this->attributeCode;
    }

    /**
     * Get attribute label
     */
    public function getAttributeLabel(): string
    {
        return $this->attributeLabel;
    }

    /**
     * Get backend type
     */
    public function getBackendType(): string
    {
        return $this->backendType;
    }

    /**
     * Set backend type with validation
     */
    public function setBackendType(string $type): self
    {
        if (!in_array($type, self::BACKEND_TYPES)) {
            throw ConfigurationException::invalidValue(
                'backend_type',
                $type,
                implode(', ', self::BACKEND_TYPES)
            );
        }
        $this->backendType = $type;
        return $this;
    }

    /**
     * Get frontend type
     */
    public function getFrontendType(): string
    {
        return $this->frontendType;
    }

    /**
     * Set frontend type with validation
     */
    public function setFrontendType(string $type): self
    {
        if (!in_array($type, self::FRONTEND_TYPES)) {
            throw ConfigurationException::invalidValue(
                'frontend_type',
                $type,
                implode(', ', self::FRONTEND_TYPES)
            );
        }
        $this->frontendType = $type;
        return $this;
    }

    /**
     * Is this attribute required?
     */
    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    /**
     * Is this attribute unique?
     */
    public function isUnique(): bool
    {
        return $this->isUnique;
    }

    /**
     * Is this attribute searchable?
     */
    public function isSearchable(): bool
    {
        return $this->isSearchable;
    }

    /**
     * Is this attribute filterable?
     */
    public function isFilterable(): bool
    {
        return $this->isFilterable;
    }

    /**
     * Is this attribute comparable?
     */
    public function isComparable(): bool
    {
        return $this->isComparable;
    }

    /**
     * Get default value
     */
    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }

    /**
     * Get validation rules
     */
    public function getValidationRules(): array
    {
        return $this->validationRules;
    }

    /**
     * Get source model
     */
    public function getSourceModel(): ?string
    {
        return $this->sourceModel;
    }

    /**
     * Get sort order
     */
    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    /**
     * Validate a value against this attribute's rules
     */
    public function validate($value): bool
    {
        // Check required
        if ($this->isRequired && ($value === null || $value === '')) {
            throw ValidationException::requiredField($this->attributeCode);
        }

        // If null and not required, it's valid
        if ($value === null || $value === '') {
            return true;
        }

        // Type validation based on backend type
        $this->validateType($value);

        // Custom validation rules
        $this->validateRules($value);

        return true;
    }

    /**
     * Validate type based on backend type
     */
    protected function validateType($value): void
    {
        switch ($this->backendType) {
            case 'int':
                if (!is_numeric($value) || (int)$value != $value) {
                    throw ValidationException::invalidType($this->attributeCode, 'integer', $value);
                }
                break;
            case 'decimal':
                if (!is_numeric($value)) {
                    throw ValidationException::invalidType($this->attributeCode, 'decimal', $value);
                }
                break;
            case 'datetime':
                if (!strtotime($value)) {
                    throw ValidationException::invalidType($this->attributeCode, 'datetime', $value);
                }
                break;
        }
    }

    /**
     * Validate against custom rules
     */
    protected function validateRules($value): void
    {
        foreach ($this->validationRules as $rule => $params) {
            switch ($rule) {
                case 'min':
                    if (is_numeric($value) && $value < $params) {
                        throw new ValidationException(
                            "Value for '{$this->attributeCode}' must be at least {$params}"
                        );
                    }
                    break;
                case 'max':
                    if (is_numeric($value) && $value > $params) {
                        throw new ValidationException(
                            "Value for '{$this->attributeCode}' must not exceed {$params}"
                        );
                    }
                    break;
                case 'min_length':
                    if (is_string($value) && strlen($value) < $params) {
                        throw new ValidationException(
                            "Value for '{$this->attributeCode}' must be at least {$params} characters"
                        );
                    }
                    break;
                case 'max_length':
                    if (is_string($value) && strlen($value) > $params) {
                        throw new ValidationException(
                            "Value for '{$this->attributeCode}' must not exceed {$params} characters"
                        );
                    }
                    break;
                case 'pattern':
                    if (is_string($value) && !preg_match($params, $value)) {
                        throw new ValidationException(
                            "Value for '{$this->attributeCode}' does not match required pattern"
                        );
                    }
                    break;
            }
        }
    }

    /**
     * Cast a value to the appropriate type for this attribute
     */
    public function cast($value): mixed
    {
        if ($value === null || $value === '') {
            return $this->defaultValue;
        }

        return match($this->backendType) {
            'int' => (int)$value,
            'decimal' => (float)$value,
            'varchar', 'text' => (string)$value,
            'datetime' => is_string($value) ? $value : date('Y-m-d H:i:s', $value),
            default => $value
        };
    }

    /**
     * Convert attribute to array
     */
    public function toArray(): array
    {
        return [
            'attribute_id' => $this->attributeId,
            'entity_type_id' => $this->entityTypeId,
            'attribute_code' => $this->attributeCode,
            'attribute_label' => $this->attributeLabel,
            'backend_type' => $this->backendType,
            'frontend_type' => $this->frontendType,
            'is_required' => $this->isRequired,
            'is_unique' => $this->isUnique,
            'is_searchable' => $this->isSearchable,
            'is_filterable' => $this->isFilterable,
            'is_comparable' => $this->isComparable,
            'default_value' => $this->defaultValue,
            'validation_rules' => $this->validationRules,
            'source_model' => $this->sourceModel,
            'backend_model' => $this->backendModel,
            'frontend_model' => $this->frontendModel,
            'sort_order' => $this->sortOrder,
        ];
    }
}
