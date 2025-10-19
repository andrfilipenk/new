<?php
// app/Core/Eav/Model/Attribute.php
namespace Core\Eav\Model;

/**
 * Represents an attribute definition
 */
class Attribute
{
    private string $code;
    private string $label;
    private string $backendType;
    private string $frontendType;
    private bool $isRequired = false;
    private bool $isUnique = false;
    private bool $isSearchable = false;
    private bool $isFilterable = false;
    private mixed $defaultValue = null;
    private array $validationRules = [];
    private int $sortOrder = 0;
    private ?int $attributeId = null;

    public function __construct(array $data)
    {
        $this->code = $data['code'] ?? '';
        $this->label = $data['label'] ?? '';
        $this->backendType = $data['backend_type'] ?? 'varchar';
        $this->frontendType = $data['frontend_type'] ?? 'text';
        $this->isRequired = $data['is_required'] ?? false;
        $this->isUnique = $data['is_unique'] ?? false;
        $this->isSearchable = $data['is_searchable'] ?? false;
        $this->isFilterable = $data['is_filterable'] ?? false;
        $this->defaultValue = $data['default_value'] ?? null;
        $this->validationRules = $data['validation_rules'] ?? [];
        $this->sortOrder = $data['sort_order'] ?? 0;
        $this->attributeId = $data['attribute_id'] ?? null;
    }

    public function getCode(): string { return $this->code; }
    public function getLabel(): string { return $this->label; }
    public function getBackendType(): string { return $this->backendType; }
    public function getFrontendType(): string { return $this->frontendType; }
    public function isRequired(): bool { return $this->isRequired; }
    public function isUnique(): bool { return $this->isUnique; }
    public function isSearchable(): bool { return $this->isSearchable; }
    public function isFilterable(): bool { return $this->isFilterable; }
    public function getDefaultValue(): mixed { return $this->defaultValue; }
    public function getValidationRules(): array { return $this->validationRules; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function getAttributeId(): ?int { return $this->attributeId; }
    
    public function setAttributeId(int $id): void { $this->attributeId = $id; }
    
    /**
     * Convert to array format for database storage
     */
    public function toArray(): array
    {
        return [
            'attribute_code' => $this->code,
            'attribute_label' => $this->label,
            'backend_type' => $this->backendType,
            'frontend_type' => $this->frontendType,
            'is_required' => $this->isRequired ? 1 : 0,
            'is_unique' => $this->isUnique ? 1 : 0,
            'is_searchable' => $this->isSearchable ? 1 : 0,
            'is_filterable' => $this->isFilterable ? 1 : 0,
            'default_value' => $this->defaultValue !== null ? json_encode($this->defaultValue) : null,
            'validation_rules' => !empty($this->validationRules) ? json_encode($this->validationRules) : null,
            'sort_order' => $this->sortOrder,
        ];
    }
}
