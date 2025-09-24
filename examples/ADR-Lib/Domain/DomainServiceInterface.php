<?php
// app/Core/Domain/DomainServiceInterface.php
namespace Core\Domain;

use Core\Adr\DomainResult;

/**
 * Domain Service Interface
 * 
 * Domain services encapsulate business logic that doesn't belong
 * to a specific entity or value object
 */
interface DomainServiceInterface
{
    /**
     * Execute domain service operation
     *
     * @param object $dto Data Transfer Object
     * @return DomainResult Result of the operation
     */
    public function execute(object $dto): DomainResult;

    /**
     * Validate business rules specific to this service
     *
     * @param object $dto Data Transfer Object
     * @return array Validation errors
     */
    public function validateBusinessRules(object $dto): array;
}