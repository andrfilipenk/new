<?php

namespace Core\Forms\Security;

use Core\Session\Session;

class CsrfProtection
{
    /**
     * @var string Default token field name
     */
    private const DEFAULT_TOKEN_FIELD = '_csrf_token';

    /**
     * @var string Session key for CSRF tokens
     */
    private const SESSION_KEY = 'csrf_tokens';

    /**
     * @var string Token field name
     */
    private string $tokenFieldName;

    /**
     * @var int Token lifetime in seconds (default: 2 hours)
     */
    private int $tokenLifetime = 7200;

    /**
     * @var bool Whether to rotate token after validation
     */
    private bool $rotateToken = true;

    /**
     * @var Session|null Session instance
     */
    private $session = null;

    /**
     * Create a new CSRF protection instance
     * 
     * @param string $tokenFieldName Custom token field name
     * @param Session|null $session Session instance
     */
    public function __construct(string $tokenFieldName = self::DEFAULT_TOKEN_FIELD, $session = null)
    {
        $this->tokenFieldName = $tokenFieldName;
        $this->session = $session;
        
        // Initialize session if not provided
        if ($this->session === null && session_status() === PHP_SESSION_ACTIVE) {
            $this->initializeSessionTokens();
        }
    }

    /**
     * Initialize session tokens array
     */
    private function initializeSessionTokens(): void
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
    }

    /**
     * Generate a new CSRF token
     * 
     * @param string|null $formName Optional form name for multiple forms
     * @return string The generated token
     */
    public function generateToken(?string $formName = null): string
    {
        $token = bin2hex(random_bytes(32));
        $key = $formName ?? 'default';
        
        // Store token with timestamp
        $this->storeToken($key, $token);
        
        return $token;
    }

    /**
     * Store token in session
     * 
     * @param string $key Token key
     * @param string $token Token value
     */
    private function storeToken(string $key, string $token): void
    {
        if ($this->session) {
            $tokens = $this->session->get(self::SESSION_KEY, []);
            $tokens[$key] = [
                'token' => $token,
                'time' => time()
            ];
            $this->session->set(self::SESSION_KEY, $tokens);
        } else {
            $_SESSION[self::SESSION_KEY][$key] = [
                'token' => $token,
                'time' => time()
            ];
        }
    }

    /**
     * Validate a CSRF token
     * 
     * @param string $token Token to validate
     * @param string|null $formName Optional form name
     * @return bool True if token is valid
     */
    public function validateToken(string $token, ?string $formName = null): bool
    {
        $key = $formName ?? 'default';
        $storedData = $this->getStoredToken($key);
        
        if (!$storedData) {
            return false;
        }
        
        // Check if token has expired
        if (time() - $storedData['time'] > $this->tokenLifetime) {
            $this->removeToken($key);
            return false;
        }
        
        // Validate token using timing-safe comparison
        $isValid = hash_equals($storedData['token'], $token);
        
        // Rotate token if configured
        if ($isValid && $this->rotateToken) {
            $this->removeToken($key);
        }
        
        return $isValid;
    }

    /**
     * Get stored token data
     * 
     * @param string $key Token key
     * @return array|null Token data or null if not found
     */
    private function getStoredToken(string $key): ?array
    {
        if ($this->session) {
            $tokens = $this->session->get(self::SESSION_KEY, []);
            return $tokens[$key] ?? null;
        }
        
        return $_SESSION[self::SESSION_KEY][$key] ?? null;
    }

    /**
     * Remove a token from storage
     * 
     * @param string $key Token key
     */
    private function removeToken(string $key): void
    {
        if ($this->session) {
            $tokens = $this->session->get(self::SESSION_KEY, []);
            unset($tokens[$key]);
            $this->session->set(self::SESSION_KEY, $tokens);
        } else {
            unset($_SESSION[self::SESSION_KEY][$key]);
        }
    }

    /**
     * Validate token from request data
     * 
     * @param array $data Request data
     * @param string|null $formName Optional form name
     * @return bool True if token is valid
     */
    public function validateRequest(array $data, ?string $formName = null): bool
    {
        $token = $data[$this->tokenFieldName] ?? '';
        
        if (empty($token)) {
            return false;
        }
        
        return $this->validateToken($token, $formName);
    }

    /**
     * Get token field name
     * 
     * @return string
     */
    public function getTokenFieldName(): string
    {
        return $this->tokenFieldName;
    }

    /**
     * Set token lifetime
     * 
     * @param int $seconds Lifetime in seconds
     * @return self
     */
    public function setTokenLifetime(int $seconds): self
    {
        $this->tokenLifetime = $seconds;
        return $this;
    }

    /**
     * Set whether to rotate token after validation
     * 
     * @param bool $rotate Rotation flag
     * @return self
     */
    public function setRotateToken(bool $rotate): self
    {
        $this->rotateToken = $rotate;
        return $this;
    }

    /**
     * Generate hidden input field HTML for CSRF token
     * 
     * @param string|null $formName Optional form name
     * @return string HTML input field
     */
    public function generateField(?string $formName = null): string
    {
        $token = $this->generateToken($formName);
        
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars($this->tokenFieldName, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Clean up expired tokens
     * 
     * @return int Number of tokens removed
     */
    public function cleanupExpiredTokens(): int
    {
        $count = 0;
        $tokens = $this->session 
            ? $this->session->get(self::SESSION_KEY, [])
            : ($_SESSION[self::SESSION_KEY] ?? []);
        
        foreach ($tokens as $key => $data) {
            if (time() - $data['time'] > $this->tokenLifetime) {
                $this->removeToken($key);
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Get current token for a form
     * 
     * @param string|null $formName Optional form name
     * @return string|null Current token or null if not exists
     */
    public function getCurrentToken(?string $formName = null): ?string
    {
        $key = $formName ?? 'default';
        $storedData = $this->getStoredToken($key);
        
        return $storedData['token'] ?? null;
    }

    /**
     * Check if a token exists for a form
     * 
     * @param string|null $formName Optional form name
     * @return bool
     */
    public function hasToken(?string $formName = null): bool
    {
        $key = $formName ?? 'default';
        return $this->getStoredToken($key) !== null;
    }

    /**
     * Regenerate token for a form
     * 
     * @param string|null $formName Optional form name
     * @return string New token
     */
    public function regenerateToken(?string $formName = null): string
    {
        $key = $formName ?? 'default';
        $this->removeToken($key);
        return $this->generateToken($formName);
    }

    /**
     * Get all active tokens (for debugging)
     * 
     * @return array
     */
    public function getAllTokens(): array
    {
        if ($this->session) {
            return $this->session->get(self::SESSION_KEY, []);
        }
        
        return $_SESSION[self::SESSION_KEY] ?? [];
    }

    /**
     * Clear all tokens
     * 
     * @return self
     */
    public function clearAllTokens(): self
    {
        if ($this->session) {
            $this->session->set(self::SESSION_KEY, []);
        } else {
            $_SESSION[self::SESSION_KEY] = [];
        }
        
        return $this;
    }
}
