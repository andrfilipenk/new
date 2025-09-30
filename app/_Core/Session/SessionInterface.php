<?php
// app/_Core/Session/SessionInterface.php
namespace Core\Session;

interface SessionInterface
{
    public function start(): bool;
    public function isStarted(): bool;
    public function getId(): string;
    public function setId(string $id);
    public function getName(): string;
    public function setName(string $name);
    public function has(string $key): bool;
    public function get(string $key, $default = null);
    public function set(string $key, $value);
    public function remove(string $key);
    public function clear();
    public function destroy();
}