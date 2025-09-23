<?php
// app/Core/Dto/DtoInterface.php
namespace Core\Dto;

/**
 * Data Transfer Object Interface
 * 
 * DTOs are used to transfer data between layers without exposing
 * internal implementation details
 */
interface DtoInterface
{
    /**
     * Create DTO from array data
     *
     * @param array $data Input data
     * @return static DTO instance
     */
    public static function fromArray(array $data): self;

    /**
     * Convert DTO to array
     *
     * @return array DTO data as array
     */
    public function toArray(): array;

    /**
     * Validate DTO data
     *
     * @return array Validation errors (empty if valid)
     */
    public function validate(): array;
}