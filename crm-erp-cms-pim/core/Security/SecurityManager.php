<?php

namespace Core\Security;

use Core\Session\SessionManager;

/**
 * Security Manager
 * 
 * Input sanitization, CSRF protection, and XSS prevention
 */
class SecurityManager
{
    /**
     * Sanitize input
     */
    public static function sanitizeInput(string $input, string $type = 'string'): string
    {
        $input = trim($input);
        
        return match($type) {
            'email' => filter_var($input, FILTER_SANITIZE_EMAIL),
            'url' => filter_var($input, FILTER_SANITIZE_URL),
            'int' => filter_var($input, FILTER_SANITIZE_NUMBER_INT),
            'float' => filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            default => htmlspecialchars($input, ENT_QUOTES, 'UTF-8'),
        };
    }

    /**
     * Escape output for HTML
     */
    public static function escape(string $output): string
    {
        return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        SessionManager::set('_csrf_token', $token);
        SessionManager::set('_csrf_token_time', time());
        return $token;
    }

    /**
     * Get CSRF token
     */
    public static function getCsrfToken(): string
    {
        if (!SessionManager::has('_csrf_token')) {
            return self::generateCsrfToken();
        }

        // Regenerate if expired (2 hours)
        $tokenTime = SessionManager::get('_csrf_token_time', 0);
        if (time() - $tokenTime > 7200) {
            return self::generateCsrfToken();
        }

        return SessionManager::get('_csrf_token');
    }

    /**
     * Validate CSRF token
     */
    public static function validateCsrfToken(string $token): bool
    {
        $sessionToken = SessionManager::get('_csrf_token');
        
        if (!$sessionToken) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Set security headers
     */
    public static function setSecurityHeaders(): void
    {
        $headers = \Core\Config\Config::get('security.headers', []);
        
        foreach ($headers as $name => $value) {
            if (!headers_sent()) {
                header("{$name}: {$value}");
            }
        }
    }

    /**
     * Filter XSS
     */
    public static function filterXSS(string $input): string
    {
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Remove dangerous tags
        $input = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $input);
        $input = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $input);
        
        // Remove javascript: and data: protocols
        $input = preg_replace('/javascript:/i', '', $input);
        $input = preg_replace('/data:text\/html/i', '', $input);
        
        return $input;
    }

    /**
     * Clean filename
     */
    public static function cleanFilename(string $filename): string
    {
        // Get extension
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        
        // Remove special characters
        $basename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $basename);
        
        // Limit length
        $basename = substr($basename, 0, 100);
        
        return $basename . '.' . $extension;
    }

    /**
     * Generate random string
     */
    public static function randomString(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}
