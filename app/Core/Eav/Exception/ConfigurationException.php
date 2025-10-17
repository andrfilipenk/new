<?php
// app/Core/Eav/Exception/ConfigurationException.php
namespace Core\Eav\Exception;

/**
 * Exception for configuration-related errors
 */
class ConfigurationException extends EavException
{
    public function getHttpStatusCode(): int
    {
        return 500;
    }
}
