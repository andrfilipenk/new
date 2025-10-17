<?php
// app/Eav/Exception/EntityException.php
namespace Eav\Exception;

/**
 * Entity Exception
 * 
 * Thrown when entity operations fail.
 */
class EntityException extends EavException
{
    /**
     * Create exception for entity not found
     */
    public static function notFound(int $entityId, string $entityType = ''): self
    {
        $message = "Entity with ID {$entityId}" . 
                   ($entityType ? " of type '{$entityType}'" : '') . 
                   " not found";
        return (new self($message))
            ->setContext(['entity_id' => $entityId, 'entity_type' => $entityType]);
    }

    /**
     * Create exception for invalid entity type
     */
    public static function invalidType(string $entityType): self
    {
        return (new self("Invalid or unknown entity type: {$entityType}"))
            ->setContext(['entity_type' => $entityType]);
    }

    /**
     * Create exception for entity save failure
     */
    public static function saveFailure(string $reason, \Throwable $previous = null): self
    {
        return new self("Failed to save entity: {$reason}", 0, $previous);
    }

    /**
     * Create exception for entity delete failure
     */
    public static function deleteFailure(string $reason, \Throwable $previous = null): self
    {
        return new self("Failed to delete entity: {$reason}", 0, $previous);
    }
}
