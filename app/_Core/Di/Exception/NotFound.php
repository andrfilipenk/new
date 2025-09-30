<?php
// app/_Core/Di/Exception/NotFound.php
namespace Core\Di\Exception;

use Core\Di\Interface\NotFoundException;

/**
 * Container exception classes
 */
class NotFound extends \Exception implements NotFoundException
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}