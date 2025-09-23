<?php
// examples/Adr/User/Dto/CreateUserDto.php
namespace Examples\Adr\User\Dto;

use Core\Dto\AbstractDto;

/**
 * Create User DTO - Data Transfer Object for user creation
 */
class CreateUserDto extends AbstractDto
{
    public string $name;
    public string $email;
    public string $password;
    public ?string $kuhnle_id = null;
    public ?array $roles = [];

    /**
     * Validation rules for user creation
     */
    public function validate(): array
    {
        $errors = [];
        
        // Required fields validation
        $errors = array_merge($errors, $this->validateRequired(['name', 'email', 'password']));
        
        // Type validation
        $errors = array_merge($errors, $this->validateTypes([
            'name' => 'string',
            'email' => 'string',
            'password' => 'string'
        ]));
        
        // Business rules validation
        if (isset($this->email) && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        
        if (isset($this->password) && strlen($this->password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }
        
        if (isset($this->name) && strlen($this->name) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        }
        
        return $errors;
    }

    /**
     * Get sanitized data for domain processing
     */
    public function getSanitizedData(): array
    {
        return [
            'name' => trim($this->name),
            'email' => strtolower(trim($this->email)),
            'password' => $this->password, // Will be hashed in domain
            'kuhnle_id' => $this->kuhnle_id,
            'roles' => $this->roles ?? ['user']
        ];
    }
}