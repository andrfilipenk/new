<?php
// app/Core/Eav/Exception/SynchronizationException.php
namespace Core\Eav\Exception;

/**
 * Exception for schema synchronization errors
 */
class SynchronizationException extends EavException
{
    public function getHttpStatusCode(): int
    {
        return 500;
    }
}
