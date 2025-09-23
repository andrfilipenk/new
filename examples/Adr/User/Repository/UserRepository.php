<?php
// examples/Adr/User/Repository/UserRepository.php
namespace Examples\Adr\User\Repository;

use Core\Domain\AbstractRepository;
use Module\Admin\Models\User;

/**
 * User Repository - Data access layer for User entities
 */
class UserRepository extends AbstractRepository
{
    protected function getModelClass(): string
    {
        return User::class;
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Check if email already exists
     */
    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    /**
     * Find users by role
     */
    public function findByRole(string $role): array
    {
        return $this->findBy(['role' => $role]);
    }

    /**
     * Find active users
     */
    public function findActiveUsers(): array
    {
        return $this->findBy(['status' => 'active']);
    }

    /**
     * Create user with hashed password
     */
    public function createUser(array $userData): User
    {
        // Hash password before saving
        if (isset($userData['password'])) {
            $userData['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        }
        
        return $this->create($userData);
    }

    /**
     * Get user statistics
     */
    public function getUserStats(): array
    {
        return [
            'total' => $this->count(),
            'active' => $this->count(['status' => 'active']),
            'inactive' => $this->count(['status' => 'inactive']),
            'recent' => $this->count(['created_at' => ['>=', date('Y-m-d', strtotime('-30 days'))]])
        ];
    }
}