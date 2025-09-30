<?php
// app/_Core/Cookie/CookieInterface.php
namespace Core\Cookie;

interface CookieInterface
{
    public function set(string $name, string $value, array $options = []): bool;
    public function get(string $name, $default = null);
    public function has(string $name): bool;
    public function delete(string $name, array $options = []): bool;
    public function setDefaults(array $defaults): void;
    public function getDefaults(): array;
}