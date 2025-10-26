<?php

/**
 * Security Configuration
 */

return [
    /*
    |--------------------------------------------------------------------------
    | CSRF Protection
    |--------------------------------------------------------------------------
    */
    'csrf' => [
        'enabled' => true,
        'token_name' => '_token',
        'header_name' => 'X-CSRF-TOKEN',
        'token_length' => 40,
        'expire' => 7200, // 2 hours in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Hashing
    |--------------------------------------------------------------------------
    */
    'password' => [
        'algorithm' => PASSWORD_ARGON2ID,
        'memory_cost' => 2048,
        'time_cost' => 4,
        'threads' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    */
    'session' => [
        'lifetime' => 120, // minutes
        'expire_on_close' => false,
        'encrypt' => false,
        'http_only' => true,
        'secure' => env('SESSION_SECURE_COOKIE', false),
        'same_site' => 'lax', // lax, strict, none
        'path' => '/',
        'domain' => env('SESSION_DOMAIN', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'enabled' => true,
        'max_attempts' => 60,
        'decay_minutes' => 1,
        'login_max_attempts' => 5,
        'login_decay_minutes' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Lockout
    |--------------------------------------------------------------------------
    */
    'lockout' => [
        'enabled' => true,
        'max_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Secure Headers
    |--------------------------------------------------------------------------
    */
    'headers' => [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    */
    'csp' => [
        'enabled' => false,
        'default-src' => ["'self'"],
        'script-src' => ["'self'", "'unsafe-inline'"],
        'style-src' => ["'self'", "'unsafe-inline'"],
        'img-src' => ["'self'", 'data:', 'https:'],
        'font-src' => ["'self'"],
        'connect-src' => ["'self'"],
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Upload MIME Types
    |--------------------------------------------------------------------------
    */
    'allowed_upload_mimes' => [
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        // Archives
        'application/zip',
        'application/x-rar-compressed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Maximum Upload Size (in KB)
    |--------------------------------------------------------------------------
    */
    'max_upload_size' => 10240, // 10 MB
];
