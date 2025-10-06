<?php
namespace Core\Database;

use Core\Exception\BaseException;

// Database Exceptions
class QueryException extends BaseException 
{
    public function __construct(string $message, string $sql = '', array $bindings = [])
    {
        parent::__construct($message, 'Database operation failed', [
            'sql' => $sql,
            'bindings' => $bindings
        ]);
    }
    public function getHttpStatusCode(): int { return 500; }
}
