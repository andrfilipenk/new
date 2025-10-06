<?php
namespace Core\Validation;

use Core\Exception\BaseException;

class ValidationException extends BaseException
{
    private array $errors;
    
    public function __construct(array $errors, string $message = 'Validation failed')
    {
        $this->errors = $errors;
        parent::__construct($message, 'Invalid input provided', ['errors' => $errors]);
    }
    public function getHttpStatusCode(): int { return 422; }
    public function getErrors(): array { return $this->errors; }
}