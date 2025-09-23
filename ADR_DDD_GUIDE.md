# ADR (Action-Domain-Responder) + DDD Integration Guide

## Overview

This document explains how to implement the ADR (Action-Domain-Responder) pattern with Domain-Driven Design (DDD) principles in your framework to achieve clean data transfer between layers.

## What is ADR?

ADR is a refinement of MVC that provides better separation of concerns:

- **Action**: Handles HTTP input and orchestrates the request
- **Domain**: Contains pure business logic (framework-agnostic)  
- **Responder**: Converts domain results to HTTP responses

## Why ADR + DDD?

### ✅ **Benefits**
- **Clean Architecture**: Clear separation between HTTP, business logic, and data
- **Framework Independence**: Domain logic has no framework dependencies
- **Testability**: Each layer can be tested in isolation
- **Reusability**: Business logic can be reused across different interfaces
- **Type Safety**: DTOs provide strong typing for data transfer
- **Maintainability**: Changes in one layer don't affect others

### 🔄 **ADR Flow**
```
HTTP Request → Action → Domain → Repository → Domain → Responder → HTTP Response
```

## Core Components

### 1. **Data Transfer Objects (DTOs)**

DTOs transfer data between layers without exposing internal structure:

```php
// examples/Adr/User/Dto/CreateUserDto.php
class CreateUserDto extends AbstractDto
{
    public string $name;
    public string $email;
    public string $password;
    public ?string $kuhnle_id = null;
    
    public function validate(): array
    {
        $errors = $this->validateRequired(['name', 'email', 'password']);
        // Add business validation rules
        return $errors;
    }
}
```

### 2. **Repository Layer**

Abstracts data access for domain services:

```php
// examples/Adr/User/Repository/UserRepository.php
class UserRepository extends AbstractRepository
{
    protected function getModelClass(): string
    {
        return User::class;
    }
    
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }
    
    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }
}
```

### 3. **Domain Services**

Contain pure business logic and rules:

```php
// examples/Adr/User/Domain/CreateUserDomain.php
class CreateUserDomain extends AbstractDomain
{
    private UserRepository $userRepository;
    
    protected function executeOperation(object $dto): mixed
    {
        $userData = $dto->getSanitizedData();
        $user = $this->userRepository->createUser($userData);
        return ['user' => $user->toArray()];
    }
    
    public function validateDomainRules(object $dto): array
    {
        $errors = [];
        
        // Business rule: Email must be unique
        if ($this->userRepository->emailExists($dto->email)) {
            $errors['email'] = 'Email already exists';
        }
        
        return $errors;
    }
}
```

### 4. **Actions**

Handle HTTP input and call domain services:

```php
// examples/Adr/User/Action/CreateUserAction.php
class CreateUserAction extends AbstractAction
{
    public function createDto(Request $request): object
    {
        return CreateUserDto::fromArray([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ]);
    }
    
    protected function getValidationRules(): array
    {
        return [
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
        ];
    }
}
```

### 5. **Responders**

Convert domain results to HTTP responses:

```php
// examples/Adr/User/Responder/UserResponder.php
class UserResponder extends AbstractResponder
{
    protected function formatSuccessData($data): array
    {
        return [
            'success' => true,
            'data' => $data,
            'timestamp' => date('c')
        ];
    }
    
    protected function shouldReturnJson(Request $request): bool
    {
        return $request->isAjax() || 
               str_contains($request->uri(), '/api/');
    }
}
```

### 6. **ADR Controllers**

Orchestrate the ADR flow:

```php
// examples/Adr/User/Controller/UserAdrController.php
class UserAdrController extends AdrController
{
    public function createUserAction()
    {
        return $this->adr(
            CreateUserAction::class,
            UserResponder::class
        );
    }
    
    // Auto-wire based on naming conventions
    public function updateUserAction()
    {
        return $this->autoAdr('updateUser');
    }
}
```

## Implementation Guide

### Step 1: Register ADR Components

```php
// examples/Adr/AdrServiceProvider.php
class AdrServiceProvider
{
    public function register(ContainerInterface $di): void
    {
        // Register Repository
        $di->set('userRepository', function() {
            return new UserRepository();
        });

        // Register Domain Services
        $di->set('createUserDomain', function($di) {
            return new CreateUserDomain($di->get('userRepository'));
        });

        // Register Actions
        $di->set('CreateUserAction', function($di) {
            return new CreateUserAction($di->get('createUserDomain'));
        });

        // Register Responders
        $di->set('UserResponder', function() {
            return new UserResponder();
        });
    }
}
```

### Step 2: Update Bootstrap

```php
// app/bootstrap.php
$adrProvider = new \Examples\Adr\AdrServiceProvider();
$adrProvider->register($di);
```

### Step 3: Configure Routes

```php
// app/config.php
'routes' => [
    '/users/create' => [
        'module' => 'Examples\\Adr\\User',
        'controller' => 'UserAdr',
        'action' => 'createUser'
    ]
]
```

## Migration Strategy

### 🔄 **Gradual Migration**

You can migrate incrementally:

1. **Start with new features** using ADR
2. **Keep existing MVC** for simple operations
3. **Refactor complex operations** to ADR when needed
4. **Mix approaches** as appropriate

### 📋 **When to Use ADR**

✅ **Use ADR for:**
- Complex business operations
- Operations requiring validation
- API endpoints
- Operations with multiple data sources
- Reusable business logic

❌ **Traditional MVC for:**
- Simple CRUD operations
- Read-only views
- Basic data display
- Prototyping

## Testing Benefits

### Unit Testing Each Layer

```php
// Test DTO
$dto = CreateUserDto::fromArray($data);
$this->assertEmpty($dto->validate());

// Test Domain (no HTTP dependencies)
$domain = new CreateUserDomain($mockRepository);
$result = $domain->execute($dto);
$this->assertTrue($result->isSuccess());

// Test Action
$mockRequest = $this->createMockRequest();
$action = new CreateUserAction($mockDomain);
$result = $action->execute($mockRequest);

// Test Responder
$mockResult = DomainResult::success($data);
$responder = new UserResponder();
$response = $responder->respond($mockResult, $mockRequest);
```

## Performance Considerations

### ✅ **Optimizations**
- **Lazy loading**: DTOs and repositories
- **Caching**: Domain results when appropriate
- **Connection pooling**: For repository layer
- **Response streaming**: For large datasets

### 📊 **Monitoring**
- Track domain operation performance
- Monitor DTO validation time
- Measure response generation time

## Best Practices

### 1. **DTO Design**
```php
class UserDto extends AbstractDto
{
    // Use typed properties
    public string $email;
    public int $age;
    
    // Implement validation
    public function validate(): array
    {
        return $this->validateRequired(['email', 'age']);
    }
    
    // Provide transformation methods
    public function getSanitizedEmail(): string
    {
        return strtolower(trim($this->email));
    }
}
```

### 2. **Domain Services**
```php
class UserDomain extends AbstractDomain
{
    // Keep framework-agnostic
    // No HTTP request/response objects
    // Focus on business rules
    // Return domain results, not HTTP responses
}
```

### 3. **Repository Pattern**
```php
interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;
    public function save(User $user): User;
}

class UserRepository implements UserRepositoryInterface
{
    // Implement data access logic
}
```

## Error Handling

### Domain-Level Errors
```php
// Domain returns structured errors
return DomainResult::failure([
    'business_rule' => 'User email must be unique',
    'field' => 'email'
]);
```

### HTTP-Level Errors
```php
// Responder converts to appropriate HTTP response
if ($result->hasValidationErrors()) {
    return $this->validationError($result->getValidationErrors(), $request);
}
```

## Integration Examples

### API Endpoint
```php
// POST /api/users
class ApiUserController extends AdrController
{
    public function createAction()
    {
        return $this->adr(
            CreateUserAction::class,
            JsonUserResponder::class  // Always returns JSON
        );
    }
}
```

### Web Form
```php
// POST /users/create
class WebUserController extends AdrController  
{
    public function createAction()
    {
        return $this->adr(
            CreateUserAction::class,
            HtmlUserResponder::class  // Returns HTML with redirects
        );
    }
}
```

## Troubleshooting

### Common Issues

1. **Circular Dependencies**: Use factories or lazy loading
2. **DTO Validation**: Keep validation rules in DTO, not Action
3. **Domain Purity**: Avoid HTTP objects in domain layer
4. **Response Types**: Use content negotiation in Responder

### Debug Tools

```php
// Enable domain result debugging
$result->addMetadata('debug', [
    'execution_time' => $executionTime,
    'memory_usage' => memory_get_usage(),
    'sql_queries' => $queryLog
]);
```

## Conclusion

ADR + DDD provides a clean, maintainable architecture for your framework:

- **Clear separation** of HTTP, business logic, and data concerns
- **Framework independence** for business rules
- **Easy testing** with isolated components  
- **Gradual adoption** alongside existing MVC
- **Type safety** with DTOs
- **Flexible responses** with content negotiation

The implementation maintains full compatibility with your existing framework while providing a path toward cleaner, more maintainable code architecture.

---

**Next Steps:**
1. Run the demo: `php examples/adr-integration-demo.php`
2. Implement a simple ADR flow for one feature
3. Gradually migrate complex operations to ADR
4. Extend the pattern for your specific domain needs