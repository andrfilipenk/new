<?php
// app/Core/Chart/Exception/ChartException.php
namespace Core\Chart\Exception;

use Exception;

/**
 * Chart-specific exception class
 */
class ChartException extends Exception
{
    public function __construct(string $message = '', int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}