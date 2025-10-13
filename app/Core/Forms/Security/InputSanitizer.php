<?php
/**
 * InputSanitizer Class
 * 
 * Provides input sanitization to prevent XSS attacks and other
 * security vulnerabilities through context-aware filtering.
 * 
 * @package Core\Forms\Security
 * @since 2.0.0
 */

namespace Core\Forms\Security;

class InputSanitizer
{
    /**
     * @var array Default sanitization rules
     */
    private array $defaultRules = [
        'strip_tags' => true,
        'trim' => true,
        'normalize_whitespace' => true
    ];

    /**
     * @var array Custom sanitizers
     */
    private static array $customSanitizers = [];

    /**
     * Sanitize input data
     * 
     * @param mixed $data Data to sanitize
     * @param array $rules Sanitization rules
     * @return mixed Sanitized data
     */
    public function sanitize(mixed $data, array $rules = []): mixed
    {
        $rules = array_merge($this->defaultRules, $rules);
        
        if (is_array($data)) {
            return $this->sanitizeArray($data, $rules);
        }
        
        if (is_string($data)) {
            return $this->sanitizeString($data, $rules);
        }
        
        return $data;
    }

    /**
     * Sanitize array recursively
     * 
     * @param array $data Array to sanitize
     * @param array $rules Sanitization rules
     * @return array Sanitized array
     */
    private function sanitizeArray(array $data, array $rules): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            $sanitizedKey = $this->sanitizeString($key, ['strip_tags' => true, 'trim' => true]);
            $sanitized[$sanitizedKey] = $this->sanitize($value, $rules);
        }
        
        return $sanitized;
    }

    /**
     * Sanitize string value
     * 
     * @param string $value Value to sanitize
     * @param array $rules Sanitization rules
     * @return string Sanitized value
     */
    private function sanitizeString(string $value, array $rules): string
    {
        // Trim whitespace
        if ($rules['trim'] ?? false) {
            $value = trim($value);
        }
        
        // Strip HTML tags
        if ($rules['strip_tags'] ?? false) {
            $allowedTags = $rules['allowed_tags'] ?? '';
            $value = strip_tags($value, $allowedTags);
        }
        
        // Normalize whitespace
        if ($rules['normalize_whitespace'] ?? false) {
            $value = preg_replace('/\s+/', ' ', $value);
        }
        
        // HTML entity encode
        if ($rules['html_encode'] ?? false) {
            $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        // Remove null bytes
        if ($rules['remove_null_bytes'] ?? true) {
            $value = str_replace("\0", '', $value);
        }
        
        // Apply custom sanitizers
        foreach ($rules as $ruleName => $ruleConfig) {
            if (isset(self::$customSanitizers[$ruleName])) {
                $value = self::$customSanitizers[$ruleName]($value, $ruleConfig);
            }
        }
        
        return $value;
    }

    /**
     * Sanitize for HTML output
     * 
     * @param string $value Value to sanitize
     * @return string Sanitized value
     */
    public function sanitizeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize for HTML attribute
     * 
     * @param string $value Value to sanitize
     * @return string Sanitized value
     */
    public function sanitizeAttribute(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize for JavaScript
     * 
     * @param string $value Value to sanitize
     * @return string Sanitized value
     */
    public function sanitizeJavaScript(string $value): string
    {
        return json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    /**
     * Sanitize for URL
     * 
     * @param string $url URL to sanitize
     * @return string Sanitized URL
     */
    public function sanitizeUrl(string $url): string
    {
        // Remove javascript: and data: protocols
        $url = preg_replace('/^(javascript|data):/i', '', $url);
        
        // Encode URL
        return filter_var($url, FILTER_SANITIZE_URL) ?: '';
    }

    /**
     * Sanitize email address
     * 
     * @param string $email Email to sanitize
     * @return string Sanitized email
     */
    public function sanitizeEmail(string $email): string
    {
        return filter_var($email, FILTER_SANITIZE_EMAIL) ?: '';
    }

    /**
     * Sanitize integer
     * 
     * @param mixed $value Value to sanitize
     * @return int Sanitized integer
     */
    public function sanitizeInt(mixed $value): int
    {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT) !== false 
            ? (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT)
            : 0;
    }

    /**
     * Sanitize float
     * 
     * @param mixed $value Value to sanitize
     * @return float Sanitized float
     */
    public function sanitizeFloat(mixed $value): float
    {
        return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) !== false
            ? (float)filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION)
            : 0.0;
    }

    /**
     * Remove all HTML tags
     * 
     * @param string $value Value to clean
     * @return string Clean value
     */
    public function stripAllTags(string $value): string
    {
        return strip_tags($value);
    }

    /**
     * Allow only specific HTML tags
     * 
     * @param string $value Value to filter
     * @param array $allowedTags Allowed tags (e.g., ['p', 'br', 'strong'])
     * @return string Filtered value
     */
    public function allowTags(string $value, array $allowedTags): string
    {
        $allowed = '<' . implode('><', $allowedTags) . '>';
        return strip_tags($value, $allowed);
    }

    /**
     * Sanitize filename
     * 
     * @param string $filename Filename to sanitize
     * @return string Sanitized filename
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove path traversal attempts
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Remove multiple dots
        $filename = preg_replace('/\.+/', '.', $filename);
        
        return $filename;
    }

    /**
     * Sanitize alphanumeric string
     * 
     * @param string $value Value to sanitize
     * @return string Sanitized value
     */
    public function sanitizeAlphanumeric(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $value);
    }

    /**
     * Sanitize slug (URL-friendly string)
     * 
     * @param string $value Value to sanitize
     * @return string Sanitized slug
     */
    public function sanitizeSlug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9-]/', '-', $value);
        $value = preg_replace('/-+/', '-', $value);
        return trim($value, '-');
    }

    /**
     * Remove SQL injection patterns
     * 
     * @param string $value Value to sanitize
     * @return string Sanitized value
     */
    public function removeSqlPatterns(string $value): string
    {
        // Remove common SQL injection patterns
        $patterns = [
            '/union\s+select/i',
            '/drop\s+table/i',
            '/insert\s+into/i',
            '/delete\s+from/i',
            '/update\s+\w+\s+set/i',
            '/<script/i'
        ];
        
        foreach ($patterns as $pattern) {
            $value = preg_replace($pattern, '', $value);
        }
        
        return $value;
    }

    /**
     * Register a custom sanitizer
     * 
     * @param string $name Sanitizer name
     * @param callable $callback Sanitizer callback
     * @return void
     */
    public static function registerSanitizer(string $name, callable $callback): void
    {
        self::$customSanitizers[$name] = $callback;
    }

    /**
     * Sanitize form data based on field types
     * 
     * @param array $data Form data
     * @param array $fieldTypes Field type mapping
     * @return array Sanitized data
     */
    public function sanitizeFormData(array $data, array $fieldTypes = []): array
    {
        $sanitized = [];
        
        foreach ($data as $fieldName => $value) {
            $fieldType = $fieldTypes[$fieldName] ?? 'text';
            
            $sanitized[$fieldName] = match($fieldType) {
                'email' => $this->sanitizeEmail($value),
                'url' => $this->sanitizeUrl($value),
                'number', 'integer' => $this->sanitizeInt($value),
                'float', 'decimal' => $this->sanitizeFloat($value),
                'alphanumeric' => $this->sanitizeAlphanumeric($value),
                'slug' => $this->sanitizeSlug($value),
                'filename' => $this->sanitizeFilename($value),
                default => $this->sanitize($value)
            };
        }
        
        return $sanitized;
    }

    /**
     * Deep clean array (recursive sanitization)
     * 
     * @param array $data Data to clean
     * @return array Cleaned data
     */
    public function deepClean(array $data): array
    {
        return $this->sanitizeArray($data, [
            'strip_tags' => true,
            'trim' => true,
            'normalize_whitespace' => true,
            'remove_null_bytes' => true
        ]);
    }

    /**
     * Check if string contains XSS patterns
     * 
     * @param string $value Value to check
     * @return bool True if XSS patterns detected
     */
    public function containsXss(string $value): bool
    {
        $xssPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/onerror\s*=/i',
            '/onload\s*=/i',
            '/onclick\s*=/i',
            '/<iframe/i',
            '/<object/i',
            '/<embed/i'
        ];
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        
        return false;
    }
}
