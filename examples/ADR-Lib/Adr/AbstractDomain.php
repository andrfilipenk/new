<?php
// app/Core/Adr/AbstractDomain.php
namespace Core\Adr;

/**
 * Abstract Domain - Base implementation for Domain services
 * 
 * Provides common domain service functionality
 */
abstract class AbstractDomain implements DomainInterface
{
    /**
     * Execute domain operation with validation
     */
    public function execute(object $dto): DomainResult
    {
        try {
            // Validate domain rules
            $domainErrors = $this->validateDomainRules($dto);
            if (!empty($domainErrors)) {
                return DomainResult::failure(
                    ['domain' => $domainErrors],
                    [],
                    $this->getOperationName()
                );
            }

            // Execute the actual domain logic
            $result = $this->executeOperation($dto);
            
            return DomainResult::success(
                $result,
                $this->getResultMetadata($result),
                $this->getOperationName()
            );
            
        } catch (\Exception $e) {
            return DomainResult::failure(
                ['exception' => $e->getMessage()],
                ['trace' => $e->getTraceAsString()],
                $this->getOperationName()
            );
        }
    }

    /**
     * Default domain validation (override in child classes)
     */
    public function validateDomainRules(object $dto): array
    {
        return [];
    }

    /**
     * Get metadata for successful results
     */
    protected function getResultMetadata($result): array
    {
        return [
            'timestamp' => date('c'),
            'operation' => $this->getOperationName()
        ];
    }

    /**
     * Get operation name for logging/debugging
     */
    protected function getOperationName(): string
    {
        return static::class;
    }

    /**
     * Child classes must implement the actual business logic
     */
    abstract protected function executeOperation(object $dto);
}