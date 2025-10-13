<?php
/**
 * SecurityManager Class
 * 
 * Coordinates all security features for forms including CSRF protection
 * and input sanitization. Provides centralized security management.
 * 
 * @package Core\Forms\Security
 * @since 2.0.0
 */

namespace Core\Forms\Security;

use Core\Forms\FormDefinition;

class SecurityManager
{
    /**
     * @var CsrfProtection CSRF protection instance
     */
    private CsrfProtection $csrfProtection;

    /**
     * @var InputSanitizer Input sanitizer instance
     */
    private InputSanitizer $inputSanitizer;

    /**
     * @var array Security configuration
     */
    private array $config = [
        'csrf_enabled' => true,
        'sanitize_input' => true,
        'csrf_token_lifetime' => 7200,
        'csrf_rotate_token' => true,
    ];

    /**
     * Create a new security manager
     * 
     * @param array $config Security configuration
     * @param CsrfProtection|null $csrfProtection Custom CSRF protection
     * @param InputSanitizer|null $inputSanitizer Custom input sanitizer
     */
    public function __construct(
        array $config = [],
        ?CsrfProtection $csrfProtection = null,
        ?InputSanitizer $inputSanitizer = null
    ) {
        $this->config = array_merge($this->config, $config);
        
        $this->csrfProtection = $csrfProtection ?? new CsrfProtection();
        $this->csrfProtection
            ->setTokenLifetime($this->config['csrf_token_lifetime'])
            ->setRotateToken($this->config['csrf_rotate_token']);
        
        $this->inputSanitizer = $inputSanitizer ?? new InputSanitizer();
    }

    /**
     * Apply security to form definition
     * 
     * @param FormDefinition $form Form definition
     * @return void
     */
    public function applySecurityToForm(FormDefinition $form): void
    {
        $securityConfig = $form->getSecurityConfig();
        
        // Update CSRF settings from form config
        if (isset($securityConfig['csrf_enabled'])) {
            $this->config['csrf_enabled'] = $securityConfig['csrf_enabled'];
        }
        
        if (isset($securityConfig['sanitize_input'])) {
            $this->config['sanitize_input'] = $securityConfig['sanitize_input'];
        }
    }

    /**
     * Generate CSRF token for a form
     * 
     * @param string|null $formName Optional form name
     * @return string Generated token
     */
    public function generateCsrfToken(?string $formName = null): string
    {
        if (!$this->config['csrf_enabled']) {
            return '';
        }
        
        return $this->csrfProtection->generateToken($formName);
    }

    /**
     * Validate CSRF token from request
     * 
     * @param array $data Request data
     * @param string|null $formName Optional form name
     * @return bool True if token is valid
     */
    public function validateCsrfToken(array $data, ?string $formName = null): bool
    {
        if (!$this->config['csrf_enabled']) {
            return true;
        }
        
        return $this->csrfProtection->validateRequest($data, $formName);
    }

    /**
     * Generate CSRF field HTML
     * 
     * @param string|null $formName Optional form name
     * @return string HTML input field
     */
    public function getCsrfField(?string $formName = null): string
    {
        if (!$this->config['csrf_enabled']) {
            return '';
        }
        
        return $this->csrfProtection->generateField($formName);
    }

    /**
     * Sanitize form input data
     * 
     * @param array $data Data to sanitize
     * @param array $fieldTypes Optional field type mapping
     * @return array Sanitized data
     */
    public function sanitizeInput(array $data, array $fieldTypes = []): array
    {
        if (!$this->config['sanitize_input']) {
            return $data;
        }
        
        return $this->inputSanitizer->sanitizeFormData($data, $fieldTypes);
    }

    /**
     * Process form submission with security checks
     * 
     * @param FormDefinition $form Form definition
     * @param array $data Submitted data
     * @return array Result with 'valid' and 'data'/'error' keys
     */
    public function processSubmission(FormDefinition $form, array $data): array
    {
        $formName = $form->getName();
        
        // Validate CSRF token
        if ($form->isCsrfEnabled() && !$this->validateCsrfToken($data, $formName)) {
            return [
                'valid' => false,
                'error' => 'csrf',
                'message' => 'CSRF token validation failed. Please try again.'
            ];
        }
        
        // Sanitize input
        $sanitizedData = $this->sanitizeInput($data, $this->getFieldTypes($form));
        
        return [
            'valid' => true,
            'data' => $sanitizedData
        ];
    }

    /**
     * Get field types from form definition
     * 
     * @param FormDefinition $form Form definition
     * @return array Field type mapping
     */
    private function getFieldTypes(FormDefinition $form): array
    {
        $types = [];
        
        foreach ($form->getFields() as $field) {
            $types[$field->getName()] = $field->getType();
        }
        
        return $types;
    }

    /**
     * Get CSRF protection instance
     * 
     * @return CsrfProtection
     */
    public function getCsrfProtection(): CsrfProtection
    {
        return $this->csrfProtection;
    }

    /**
     * Get input sanitizer instance
     * 
     * @return InputSanitizer
     */
    public function getInputSanitizer(): InputSanitizer
    {
        return $this->inputSanitizer;
    }

    /**
     * Check if CSRF protection is enabled
     * 
     * @return bool
     */
    public function isCsrfEnabled(): bool
    {
        return $this->config['csrf_enabled'];
    }

    /**
     * Enable or disable CSRF protection
     * 
     * @param bool $enabled CSRF enabled flag
     * @return self
     */
    public function setCsrfEnabled(bool $enabled): self
    {
        $this->config['csrf_enabled'] = $enabled;
        return $this;
    }

    /**
     * Check if input sanitization is enabled
     * 
     * @return bool
     */
    public function isSanitizationEnabled(): bool
    {
        return $this->config['sanitize_input'];
    }

    /**
     * Enable or disable input sanitization
     * 
     * @param bool $enabled Sanitization enabled flag
     * @return self
     */
    public function setSanitizationEnabled(bool $enabled): self
    {
        $this->config['sanitize_input'] = $enabled;
        return $this;
    }

    /**
     * Set security configuration
     * 
     * @param array $config Security configuration
     * @return self
     */
    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        
        // Apply configuration to components
        if (isset($config['csrf_token_lifetime'])) {
            $this->csrfProtection->setTokenLifetime($config['csrf_token_lifetime']);
        }
        
        if (isset($config['csrf_rotate_token'])) {
            $this->csrfProtection->setRotateToken($config['csrf_rotate_token']);
        }
        
        return $this;
    }

    /**
     * Get security configuration
     * 
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Clean up expired CSRF tokens
     * 
     * @return int Number of tokens removed
     */
    public function cleanupExpiredTokens(): int
    {
        return $this->csrfProtection->cleanupExpiredTokens();
    }

    /**
     * Check if data contains potential XSS
     * 
     * @param mixed $data Data to check
     * @return bool True if XSS detected
     */
    public function containsXss(mixed $data): bool
    {
        if (is_string($data)) {
            return $this->inputSanitizer->containsXss($data);
        }
        
        if (is_array($data)) {
            foreach ($data as $value) {
                if ($this->containsXss($value)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Create a security manager instance for a form
     * 
     * @param FormDefinition $form Form definition
     * @return self
     */
    public static function createForForm(FormDefinition $form): self
    {
        $manager = new self($form->getSecurityConfig());
        $manager->applySecurityToForm($form);
        return $manager;
    }
}
