<?php
namespace Core\Http;

use Core\Exception\BaseException;

class HttpException extends BaseException
{
    public function __construct(int $statusCode, string $message, string $userMessage = '')
    {
        parent::__construct($message, $userMessage ?: $this->getDefaultMessage($statusCode), [], $statusCode);
    }
    public function getHttpStatusCode(): int { return $this->getCode(); }
}