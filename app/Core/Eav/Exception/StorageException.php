<?php
// app/Core/Eav/Exception/StorageException.php
namespace Core\Eav\Exception;

/**
 * Exception for storage-related errors
 */
class StorageException extends EavException
{
    public function getHttpStatusCode(): int
    {
        return 500;
    }
}
