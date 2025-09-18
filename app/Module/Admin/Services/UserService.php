<?php
// app/Module/Admin/Services/UserService.php
namespace Module\Admin\Services;

use Core\Services\BaseService;
use Module\Admin\Models\Users;

/**
 * User service implementing business logic
 * Demonstrates super-senior PHP service layer pattern
 */
class UserService extends BaseService
{
    protected string $modelClass = Users::class;
    
    protected array $validationRules = [
        'name' => 'required|string|min:2|max:100',
        'email' => 'required|email|unique:users,email',
        'kuhnle_id' => 'required|numeric|min:1|max:9999|unique:users,kuhnle_id',
        'password' => 'required|string|min:6'
    ];
    
    protected array $defaultWith = []; // Add relations here when implemented

    /**
     * Create user with password hashing
     */
    public function create(array $data): \Core\Database\Model
    {
        // Hash password before creation
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return parent::create($data);
    }

    /**
     * Update user with password hashing
     */
    public function update(\Core\Database\Model $record, array $data): \Core\Database\Model
    {
        // Hash password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            // Remove empty password from update data
            unset($data['password']);
        }
        
        return parent::update($record, $data);
    }

    /**
     * Get active users only
     */
    public function getActiveUsers(): array
    {
        return $this->modelClass::where('active', 1)->get();
    }

    /**
     * Search users by name or email
     */
    public function searchUsers(string $query): array
    {
        return $this->modelClass::where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->get();
    }
}