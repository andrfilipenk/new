<?php
// examples/Adr/User/Domain/CreateUserDomain.php
namespace Examples\Adr\User\Domain;

use Core\Adr\AbstractDomain;
use Core\Adr\DomainResult;
use Examples\Adr\User\Repository\UserRepository;
use Examples\Adr\User\Dto\CreateUserDto;

/**
 * Create User Domain Service - Business logic for user creation
 */
class CreateUserDomain extends AbstractDomain
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Execute user creation with business rules
     */
    protected function executeOperation(object $dto): mixed
    {
        if (!$dto instanceof CreateUserDto) {
            throw new \InvalidArgumentException('Expected CreateUserDto');
        }

        // Create user through repository
        $userData = $dto->getSanitizedData();
        $user = $this->userRepository->createUser($userData);

        return [
            'user' => $user->toArray(),
            'message' => 'User created successfully'
        ];
    }

    /**
     * Validate domain-specific business rules
     */
    public function validateDomainRules(object $dto): array
    {
        if (!$dto instanceof CreateUserDto) {
            return ['dto' => 'Invalid DTO type'];
        }

        $errors = [];

        // Business rule: Email must be unique
        if ($this->userRepository->emailExists($dto->email)) {
            $errors['email'] = 'Email already exists';
        }

        // Business rule: Kuhnle ID must be unique if provided
        if ($dto->kuhnle_id && $this->userRepository->findOneBy(['kuhnle_id' => $dto->kuhnle_id])) {
            $errors['kuhnle_id'] = 'Kuhnle ID already exists';
        }

        // Business rule: Check role permissions
        if (!empty($dto->roles)) {
            $allowedRoles = ['user', 'admin', 'moderator'];
            $invalidRoles = array_diff($dto->roles, $allowedRoles);
            
            if (!empty($invalidRoles)) {
                $errors['roles'] = 'Invalid roles: ' . implode(', ', $invalidRoles);
            }
        }

        return $errors;
    }

    protected function getOperationName(): string
    {
        return 'CreateUser';
    }
}