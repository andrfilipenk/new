<?php
// examples/adr-standalone-demo.php

// Simple autoloader for demo
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../app/',
        __DIR__ . '/'
    ];
    
    foreach ($paths as $basePath) {
        $file = $basePath . str_replace('\\', '/', $class) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return true;
        }
    }
    return false;
});

// Mock classes for demo
class User {
    public $id;
    public $name;
    public $email;
    
    public function __construct($data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function toArray() {
        return get_object_vars($this);
    }
}

// Include required files
require_once __DIR__ . '/../app/Core/Dto/DtoInterface.php';
require_once __DIR__ . '/../app/Core/Dto/AbstractDto.php';
require_once __DIR__ . '/../app/Core/Adr/DomainResult.php';

/**
 * Standalone ADR + DDD Demonstration
 */

echo "=== ADR (Action-Domain-Responder) + DDD Integration Demo ===\n\n";

echo "🎯 OVERVIEW: ADR Pattern Benefits\n";
echo str_repeat("=", 50) . "\n";
echo "✓ Clean separation of HTTP, business logic, and data layers\n";
echo "✓ Framework-agnostic domain logic (pure PHP)\n";
echo "✓ Type-safe data transfer with DTOs\n";
echo "✓ Testable components in isolation\n";
echo "✓ Reusable business logic across different interfaces\n";
echo "✓ Better error handling and validation\n\n";

echo "🔄 ADR FLOW:\n";
echo "HTTP Request → Action → Domain → Repository → Domain → Responder → HTTP Response\n\n";

// 1. DTO Layer Demo
echo "1️⃣ DATA TRANSFER OBJECT (DTO) LAYER\n";
echo str_repeat("-", 40) . "\n";

// Create a simple DTO for demo
class CreateUserDto extends \Core\Dto\AbstractDto
{
    public string $name;
    public string $email;
    public string $password;
    public ?int $kuhnle_id = null;
    
    public function validate(): array
    {
        $errors = [];
        
        // Required fields
        if (empty($this->name)) $errors['name'] = 'Name is required';
        if (empty($this->email)) $errors['email'] = 'Email is required';
        if (empty($this->password)) $errors['password'] = 'Password is required';
        
        // Format validation
        if (!empty($this->email) && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email format';
        }
        
        if (!empty($this->password) && strlen($this->password) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }
        
        return $errors;
    }
}

$inputData = [
    'name' => 'John Doe',
    'email' => 'john@example.com', 
    'password' => 'secure123',
    'kuhnle_id' => 1234
];

$dto = CreateUserDto::fromArray($inputData);

echo "Created DTO from input data:\n";
echo "  Type: " . get_class($dto) . "\n";
echo "  Data: " . json_encode($dto->toArray()) . "\n";

$validationErrors = $dto->validate();
if (empty($validationErrors)) {
    echo "  Validation: ✅ PASSED\n";
} else {
    echo "  Validation: ❌ FAILED\n";
    foreach ($validationErrors as $field => $error) {
        echo "    - {$field}: {$error}\n";
    }
}

echo "\n2️⃣ DOMAIN RESULT PATTERN\n";
echo str_repeat("-", 30) . "\n";

use Core\Adr\DomainResult;

// Success result
$successResult = DomainResult::success(
    ['user' => ['id' => 1, 'name' => 'John Doe']],
    ['operation_time' => '50ms'],
    'CreateUser'
);

echo "Success Result:\n";
echo "  Success: " . ($successResult->isSuccess() ? '✅ Yes' : '❌ No') . "\n";
echo "  Data: " . json_encode($successResult->getData()) . "\n";
echo "  Metadata: " . json_encode($successResult->getMetadata()) . "\n";

// Validation error result
$validationResult = DomainResult::validationError(
    ['email' => 'Email already exists'],
    'CreateUser'
);

echo "\nValidation Error Result:\n";
echo "  Success: " . ($validationResult->isSuccess() ? '✅ Yes' : '❌ No') . "\n";
echo "  Has Validation Errors: " . ($validationResult->hasValidationErrors() ? '✅ Yes' : '❌ No') . "\n";
echo "  Validation Errors: " . json_encode($validationResult->getValidationErrors()) . "\n";

// Failure result
$failureResult = DomainResult::failure(
    ['database' => 'Connection failed'],
    ['error_code' => 'DB_001'],
    'CreateUser'
);

echo "\nFailure Result:\n";
echo "  Success: " . ($failureResult->isSuccess() ? '✅ Yes' : '❌ No') . "\n";
echo "  Errors: " . json_encode($failureResult->getErrors()) . "\n";

echo "\n3️⃣ LAYER SEPARATION BENEFITS\n";
echo str_repeat("-", 35) . "\n";

echo "Action Layer (HTTP Concern):\n";
echo "  ✓ Validates HTTP input\n";
echo "  ✓ Creates DTOs from request data\n";
echo "  ✓ Handles HTTP-specific validation\n";
echo "  ✓ No business logic\n";

echo "\nDomain Layer (Business Logic):\n";
echo "  ✓ Framework-agnostic pure PHP\n";
echo "  ✓ Enforces business rules\n";
echo "  ✓ Coordinates with repositories\n";
echo "  ✓ Returns DomainResult objects\n";

echo "\nResponder Layer (HTTP Response):\n";
echo "  ✓ Converts domain results to HTTP responses\n";
echo "  ✓ Content negotiation (JSON/HTML)\n";
echo "  ✓ Sets appropriate status codes\n";
echo "  ✓ Handles error formatting\n";

echo "\n4️⃣ PRACTICAL INTEGRATION EXAMPLE\n";
echo str_repeat("-", 40) . "\n";

// Simulate complete ADR flow
class MockUserRepository {
    private array $users = [];
    
    public function emailExists(string $email): bool {
        foreach ($this->users as $user) {
            if ($user['email'] === $email) {
                return true;
            }
        }
        return false;
    }
    
    public function createUser(array $userData): array {
        $user = array_merge($userData, ['id' => count($this->users) + 1]);
        $this->users[] = $user;
        return $user;
    }
}

class MockCreateUserDomain {
    private MockUserRepository $repository;
    
    public function __construct(MockUserRepository $repository) {
        $this->repository = $repository;
    }
    
    public function execute(CreateUserDto $dto): DomainResult {
        // Validate business rules
        $errors = [];
        if ($this->repository->emailExists($dto->email)) {
            $errors['email'] = 'Email already exists';
        }
        
        if (!empty($errors)) {
            return DomainResult::failure(['business_rules' => $errors], [], 'CreateUser');
        }
        
        // Execute business logic
        try {
            $userData = [
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => password_hash($dto->password, PASSWORD_DEFAULT),
                'kuhnle_id' => $dto->kuhnle_id
            ];
            
            $user = $this->repository->createUser($userData);
            
            return DomainResult::success($user, ['created_at' => date('c')], 'CreateUser');
            
        } catch (Exception $e) {
            return DomainResult::failure(['exception' => $e->getMessage()], [], 'CreateUser');
        }
    }
}

// Execute the flow
echo "Executing complete ADR flow:\n";

$repository = new MockUserRepository();
$domain = new MockCreateUserDomain($repository);

echo "\n1. Processing DTO through Domain...\n";
$result = $domain->execute($dto);

if ($result->isSuccess()) {
    echo "   ✅ Domain operation successful\n";
    echo "   📊 Result data: " . json_encode($result->getData()) . "\n";
    echo "   📝 Metadata: " . json_encode($result->getMetadata()) . "\n";
} else {
    echo "   ❌ Domain operation failed\n";
    echo "   🔥 Errors: " . json_encode($result->getErrors()) . "\n";
}

echo "\n2. Testing business rule validation...\n";
// Test with duplicate email
$duplicateDto = CreateUserDto::fromArray([
    'name' => 'Jane Doe',
    'email' => 'john@example.com', // Same email
    'password' => 'another123'
]);

$duplicateResult = $domain->execute($duplicateDto);
if ($duplicateResult->isFailure()) {
    echo "   ✅ Business rule enforced (email uniqueness)\n";
    echo "   🚫 Error: " . json_encode($duplicateResult->getErrors()) . "\n";
}

echo "\n5️⃣ TESTING BENEFITS\n";
echo str_repeat("-", 20) . "\n";

echo "Each layer can be tested independently:\n\n";

echo "DTO Testing:\n";
echo "  ✓ Test validation rules\n";
echo "  ✓ Test data transformation\n";
echo "  ✓ No dependencies required\n";

echo "\nDomain Testing:\n";
echo "  ✓ Test business logic in isolation\n";
echo "  ✓ Mock repository dependencies\n";
echo "  ✓ No HTTP or framework dependencies\n";

echo "\nAction Testing:\n";
echo "  ✓ Test HTTP input handling\n";
echo "  ✓ Test DTO creation\n";
echo "  ✓ Mock domain services\n";

echo "\nResponder Testing:\n";
echo "  ✓ Test response formatting\n";
echo "  ✓ Test content negotiation\n";
echo "  ✓ Test error handling\n";

echo "\n6️⃣ MIGRATION STRATEGY\n";
echo str_repeat("-", 25) . "\n";

echo "Gradual adoption approach:\n";
echo "  1️⃣ Start with new features using ADR\n";
echo "  2️⃣ Keep existing MVC for simple operations\n";
echo "  3️⃣ Refactor complex operations to ADR\n";
echo "  4️⃣ Mix approaches as needed\n";

echo "\nWhen to use ADR:\n";
echo "  ✅ Complex business operations\n";
echo "  ✅ Multiple validation layers\n";
echo "  ✅ API endpoints\n";
echo "  ✅ Reusable business logic\n";

echo "\nKeep traditional MVC for:\n";
echo "  📝 Simple CRUD operations\n";
echo "  👁️ Read-only views\n";
echo "  🏃 Rapid prototyping\n";

echo "\n🎉 SUMMARY: ADR + DDD Benefits\n";
echo str_repeat("=", 50) . "\n";
echo "✅ Clean architecture with clear boundaries\n";
echo "✅ Framework-independent business logic\n";
echo "✅ Type-safe data transfer\n";
echo "✅ Enhanced testability\n";
echo "✅ Better error handling\n";
echo "✅ Reusable components\n";
echo "✅ Gradual migration path\n";
echo "✅ Scales with application complexity\n";

echo "\n📚 Next Steps:\n";
echo "1. Review the complete implementation in /examples/Adr/\n";
echo "2. Read the comprehensive guide: ADR_DDD_GUIDE.md\n";
echo "3. Start with one simple feature using ADR\n";
echo "4. Gradually expand ADR usage\n";

echo "\n=== ADR INTEGRATION DEMO COMPLETED ===\n";