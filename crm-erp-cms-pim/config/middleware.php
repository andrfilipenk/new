<?php

return [
    /**
     * Middleware Aliases
     * 
     * Short names for middleware classes
     */
    'aliases' => [
        'auth' => Core\Middleware\AuthMiddleware::class,
        'guest' => Core\Middleware\GuestMiddleware::class,
        'csrf' => Core\Middleware\CsrfMiddleware::class,
        'cors' => Core\Middleware\CorsMiddleware::class,
        'sanitize' => Core\Middleware\SanitizeInputMiddleware::class,
        'throttle' => Core\Middleware\RateLimitMiddleware::class,
    ],
    
    /**
     * Global Middleware
     * 
     * Runs on every request
     */
    'global' => [
        Core\Middleware\CorsMiddleware::class,
        Core\Middleware\SanitizeInputMiddleware::class,
    ],
    
    /**
     * Route Middleware Groups
     */
    'groups' => [
        'web' => [
            Core\Middleware\CsrfMiddleware::class,
        ],
        
        'api' => [
            Core\Middleware\RateLimitMiddleware::class,
        ],
    ],
];
