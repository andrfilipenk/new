<?php
namespace Core\Exception;

class DispatchException extends BaseException
{
    public function getHttpStatusCode(): int { return 404; }
}