<?php
// app/Core/Adr/DomainResult.php
namespace Core\Adr;

/**
 * Domain Result - Encapsulates the result of domain operations
 * 
 * This class standardizes how domain operations communicate their results
 * to the presentation layer, including success/failure state, data, and errors.
 */
class DomainResult
{
    private bool $success;
    private $data;
    private array $errors;
    private array $metadata;
    private string $operation;

    public function __construct(
        bool $success,
        $data = null,
        array $errors = [],
        array $metadata = [],
        string $operation = ''
    ) {
        $this->success = $success;
        $this->data = $data;
        $this->errors = $errors;
        $this->metadata = $metadata;
        $this->operation = $operation;
    }

    /**
     * Create successful result
     */
    public static function success($data = null, array $metadata = [], string $operation = ''): self
    {
        return new self(true, $data, [], $metadata, $operation);
    }

    /**
     * Create failure result
     */
    public static function failure(array $errors, array $metadata = [], string $operation = ''): self
    {
        return new self(false, null, $errors, $metadata, $operation);
    }

    /**
     * Create validation error result
     */
    public static function validationError(array $validationErrors, string $operation = ''): self
    {
        return new self(false, null, ['validation' => $validationErrors], [], $operation);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return !$this->success;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getValidationErrors(): array
    {
        return $this->errors['validation'] ?? [];
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function hasValidationErrors(): bool
    {
        return !empty($this->errors['validation']);
    }

    public function addMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    public function getMetadataValue(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }
}