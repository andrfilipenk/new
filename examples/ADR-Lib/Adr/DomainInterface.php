<?php
// app/Core/Adr/DomainInterface.php
namespace Core\Adr;

/**
 * Domain Interface - Contains business logic and rules
 * 
 * Domain services are responsible for:
 * - Implementing business rules and logic
 * - Coordinating between repositories and entities
 * - Maintaining domain integrity
 * - Being framework-agnostic (no HTTP dependencies)
 */
interface DomainInterface
{
    /**
     * Execute domain operation with DTO
     *
     * @param object $dto Data Transfer Object containing operation data
     * @return DomainResult Result of domain operation
     */
    public function execute(object $dto): DomainResult;

    /**
     * Validate domain rules
     *
     * @param object $dto Data Transfer Object to validate
     * @return array Domain validation errors
     */
    public function validateDomainRules(object $dto): array;
}