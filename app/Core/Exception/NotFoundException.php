<?php
namespace Core\Exception;

class NotFoundException extends BaseException
{
    public function getHttpStatusCode(): int { return 404; }
}