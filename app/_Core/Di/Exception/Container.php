<?php
// app/_Core/Di/Exception/Container.php
namespace Core\Di\Exception;

use Core\Di\Interface\ContainerException;

class Container extends \Exception implements ContainerException 
{
    public function __construct($message = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}