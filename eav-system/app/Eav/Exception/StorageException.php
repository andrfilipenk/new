<?php
// app/Eav/Exception/StorageException.php
namespace Eav\Exception;

/**
 * Storage Exception
 * 
 * Thrown when storage operations fail.
 */
class StorageException extends EavException
{
    /**
     * Storage strategy that failed
     */
    protected string $storageStrategy = '';

    /**
     * Set storage strategy
     */
    public function setStorageStrategy(string $strategy): self
    {
        $this->storageStrategy = $strategy;
        return $this;
    }

    /**
     * Get storage strategy
     */
    public function getStorageStrategy(): string
    {
        return $this->storageStrategy;
    }

    /**
     * Create exception for storage read failure
     */
    public static function readFailure(string $strategy, string $reason, \Throwable $previous = null): self
    {
        return (new self("Failed to read from storage: {$reason}", 0, $previous))
            ->setStorageStrategy($strategy);
    }

    /**
     * Create exception for storage write failure
     */
    public static function writeFailure(string $strategy, string $reason, \Throwable $previous = null): self
    {
        return (new self("Failed to write to storage: {$reason}", 0, $previous))
            ->setStorageStrategy($strategy);
    }

    /**
     * Create exception for storage delete failure
     */
    public static function deleteFailure(string $strategy, string $reason, \Throwable $previous = null): self
    {
        return (new self("Failed to delete from storage: {$reason}", 0, $previous))
            ->setStorageStrategy($strategy);
    }

    /**
     * Create exception for unsupported storage strategy
     */
    public static function unsupportedStrategy(string $strategy): self
    {
        return (new self("Unsupported storage strategy: {$strategy}"))
            ->setStorageStrategy($strategy);
    }
}
