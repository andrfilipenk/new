<?php
namespace Core\Exception;

class DatabaseException extends BaseException {

    public function getHttpStatusCode(): int { return 500; }
}