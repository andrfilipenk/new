<?php
// app/Core/Eav/Exception/EavException.php
namespace Core\Eav\Exception;

use Core\Exception\BaseException;

/**
 * Base exception for all EAV-related errors
 */
abstract class EavException extends BaseException
{
    public function getHttpStatusCode(): int
    {
        return 500;
    }
}
