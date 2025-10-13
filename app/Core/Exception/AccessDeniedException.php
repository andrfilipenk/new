<?php
namespace Core\Exception;

class AccessDeniedException extends BaseException
{
    public function getHttpStatusCode(): int { return 403; }
}