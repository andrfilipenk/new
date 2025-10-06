<?php
// app/_Core/Exception/BaseException.php
namespace Core\Exception;

abstract class BaseException extends \Exception
{
    protected array $context = [];
    protected string $userMessage;
    
    public function __construct(
        string $message,
        string $userMessage = '',
        array $context = [],
        int $code = 0,
        ?\Throwable $previous = null) 
    {
        $this->context = $context;
        $this->userMessage = $userMessage ?: 'An error occurred';
        parent::__construct($message, $code, $previous);
    }
    
    public function getContext(): array { return $this->context; }
    public function getUserMessage(): string { return $this->userMessage; }
    abstract public function getHttpStatusCode(): int;
}