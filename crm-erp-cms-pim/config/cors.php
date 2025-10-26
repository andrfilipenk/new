<?php

return [
    /**
     * Allowed Origins
     * 
     * Use ['*'] to allow all origins (not recommended for production)
     */
    'allowed_origins' => ['*'],
    
    /**
     * Allowed HTTP Methods
     */
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    
    /**
     * Allowed Headers
     */
    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'X-CSRF-TOKEN',
        'Accept',
        'Origin',
    ],
    
    /**
     * Exposed Headers
     */
    'exposed_headers' => [],
    
    /**
     * Max Age (seconds)
     */
    'max_age' => 86400,
    
    /**
     * Support Credentials
     */
    'supports_credentials' => true,
];
