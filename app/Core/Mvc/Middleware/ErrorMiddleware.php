<?php
namespace Core\Mvc\Middleware;

use Core\Di\Interface\Container;

class ErrorMiddleware implements MiddlewareInterface
{
    public function handle(Container $di, string $module, string $controller, string $action): bool
    {
        try {
            return true;
        } catch (\Throwable $e) {
            $di->get('response')->setStatusCode($e instanceof \Core\Exception\NotFoundException ? 404 : 500)
                ->setContent($e->getMessage());
            $di->get('eventsManager')->trigger('app:error', [$e]);
            return false;
        }
    }
}