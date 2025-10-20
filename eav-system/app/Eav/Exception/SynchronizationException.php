<?php
// app/Eav/Exception/SynchronizationException.php
namespace Eav\Exception;

/**
 * Synchronization Exception
 * 
 * Thrown when schema synchronization operations fail.
 */
class SynchronizationException extends EavException
{
    /**
     * Failed operations
     */
    protected array $failedOperations = [];

    /**
     * Set failed operations
     */
    public function setFailedOperations(array $operations): self
    {
        $this->failedOperations = $operations;
        return $this;
    }

    /**
     * Get failed operations
     */
    public function getFailedOperations(): array
    {
        return $this->failedOperations;
    }

    /**
     * Create exception for schema analysis failure
     */
    public static function analysisFailure(string $reason, \Throwable $previous = null): self
    {
        return new self("Schema analysis failed: {$reason}", 0, $previous);
    }

    /**
     * Create exception for migration execution failure
     */
    public static function migrationFailure(string $operation, \Throwable $previous = null): self
    {
        return (new self("Migration operation failed: {$operation}", 0, $previous))
            ->setFailedOperations([$operation]);
    }

    /**
     * Create exception for backup failure
     */
    public static function backupFailure(string $reason, \Throwable $previous = null): self
    {
        return new self("Schema backup failed: {$reason}", 0, $previous);
    }

    /**
     * Create exception for restore failure
     */
    public static function restoreFailure(string $reason, \Throwable $previous = null): self
    {
        return new self("Schema restore failed: {$reason}", 0, $previous);
    }
}
