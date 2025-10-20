<?php
// app/Core/Eav/Exception/EntityException.php
namespace Core\Eav\Exception;

/**
 * Exception for entity-related errors
 */
class EntityException extends EavException
{
    public function getHttpStatusCode(): int
    {
        return 404;
    }
}
