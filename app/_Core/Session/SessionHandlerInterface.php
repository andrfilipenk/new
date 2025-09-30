<?php
// app/_Core/Session/SessionHandlerInterface.php
namespace Core\Session;

interface SessionHandlerInterface
{
    public function open(string $savePath, string $sessionName): bool;
    public function close(): bool;
    public function read(string $sessionId): string;
    public function write(string $sessionId, string $sessionData): bool;
    public function destroy(string $sessionId): bool;
    public function gc(int $maxlifetime): int;
}