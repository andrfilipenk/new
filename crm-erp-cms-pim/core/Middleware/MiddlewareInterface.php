<?php

namespace Core\Middleware;

use Core\Request;
use Core\Response;

/**
 * Middleware Interface
 * 
 * All middleware must implement this interface
 */
interface MiddlewareInterface
{
    /**
     * Handle the request
     * 
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response;
}
