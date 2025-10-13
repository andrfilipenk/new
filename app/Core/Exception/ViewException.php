<?php
namespace Core\Exception;

class ViewException extends BaseException
{
    public function getHttpStatusCode(): int { return 404; }
}