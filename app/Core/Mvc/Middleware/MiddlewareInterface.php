<?php
// app/Core/Mvc/Middleware/MiddlewareInterface.php
namespace Core\Mvc\Middleware;

use Core\Di\Interface\Container;

interface MiddlewareInterface
{
    public function handle(Container $di, string $module, string $controller, string $action): bool;
}