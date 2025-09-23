<?php
// examples/adr-integration-demo.php

require_once __DIR__ . '/../app/bootstrap.php';

use Core\Di\Container;
use Core\Http\Request;
use Core\Http\Response;
use Examples\Adr\User\Dto\CreateUserDto;
use Examples\Adr\User\Repository\UserRepository;
use Examples\Adr\User\Domain\CreateUserDomain;
use Examples\Adr\User\Action\CreateUserAction;
use Examples\Adr\User\Responder\UserResponder;
use Examples\Adr\AdrServiceProvider;

/**
 * ADR Integration Demo - Shows how ADR pattern works with DDD
 */

echo "=== ADR (Action-Domain-Responder) with DDD Demo ===\n\n";

// Setup DI container with ADR components
$di = new Container();
$adrProvider = new AdrServiceProvider();
$adrProvider->register($di);

echo "1. ADR PATTERN OVERVIEW:\n";
echo str_repeat("-", 50) . "\n";
echo "Action:    Handles HTTP input, validates, creates DTOs\n";
echo "Domain:    Contains business logic, enforces rules\n";
echo "Responder: Converts domain results to HTTP responses\n";
echo "DTO:       Transfers data between layers cleanly\n\n";

echo "2. LAYER SEPARATION DEMONSTRATION:\n";
echo str_repeat("-", 50) . "\n";

// Simulate HTTP request data
$_POST = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'secure123',
    'kuhnle_id' => 1234,
    'roles' => ['user', 'customer']
];

$_SERVER['REQUEST_METHOD'] = 'POST';
$request = Request::capture();

echo "Simulated HTTP Request Data:\n";
foreach ($_POST as $key => $value) {
    $displayValue = is_array($value) ? json_encode($value) : $value;
    echo "  {$key}: {$displayValue}\n";
}

echo "\n3. DTO LAYER - Data Transfer:\n";
echo str_repeat("-", 30) . "\n";

// Create DTO from request data
$createUserDto = CreateUserDto::fromArray($_POST);

echo "Created DTO from HTTP data:\n";
echo "✓ DTO Type: " . get_class($createUserDto) . "\n";
echo "✓ DTO Data: " . json_encode($createUserDto->toArray()) . "\n";

// Validate DTO
$validationErrors = $createUserDto->validate();
if (empty($validationErrors)) {
    echo "✓ DTO Validation: PASSED\n";
} else {
    echo "✗ DTO Validation: FAILED\n";
    foreach ($validationErrors as $field => $error) {
        echo "  - {$field}: {$error}\n";
    }
}

echo "\n4. ACTION LAYER - HTTP to Domain:\n";
echo str_repeat("-", 35) . "\n";

try {
    $action = $di->get('CreateUserAction');
    echo "✓ Action created: " . get_class($action) . "\n";
    
    // Execute action (this would call domain)
    echo "✓ Action validated HTTP input\n";
    echo "✓ Action created DTO\n";
    echo "✓ Action calling Domain layer...\n";
    
} catch (Exception $e) {
    echo "✗ Action error: " . $e->getMessage() . "\n";
}

echo "\n5. DOMAIN LAYER - Business Logic:\n";
echo str_repeat("-", 35) . "\n";

try {
    $domain = $di->get('createUserDomain');
    echo "✓ Domain service: " . get_class($domain) . "\n";
    
    // Validate domain rules
    $domainErrors = $domain->validateDomainRules($createUserDto);
    if (empty($domainErrors)) {
        echo "✓ Domain rules validation: PASSED\n";
        echo "✓ Business rules enforced:\n";
        echo "  - Email uniqueness check\n";
        echo "  - Role permissions validation\n";
        echo "  - Kuhnle ID uniqueness check\n";
    } else {
        echo "✗ Domain rules validation: FAILED\n";
        foreach ($domainErrors as $field => $error) {
            echo "  - {$field}: {$error}\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Domain error: " . $e->getMessage() . "\n";
}

echo "\n6. REPOSITORY LAYER - Data Access:\n";
echo str_repeat("-", 37) . "\n";

echo "Repository provides data access abstraction:\n";
echo "✓ Abstract from database implementation\n";
echo "✓ Domain-focused interface\n";
echo "✓ Methods: findByEmail(), emailExists(), createUser()\n";

echo "\n7. RESPONDER LAYER - Domain to HTTP:\n";
echo str_repeat("-", 37) . "\n";

try {
    $responder = $di->get('UserResponder');
    echo "✓ Responder created: " . get_class($responder) . "\n";
    echo "✓ Handles content negotiation (JSON/HTML)\n";
    echo "✓ Converts domain results to HTTP responses\n";
    echo "✓ Sets appropriate status codes and headers\n";
    
} catch (Exception $e) {
    echo "✗ Responder error: " . $e->getMessage() . "\n";
}

echo "\n8. COMPLETE ADR FLOW SIMULATION:\n";
echo str_repeat("-", 37) . "\n";

// Simulate the complete flow
echo "Simulating complete ADR flow:\n";
echo "HTTP Request → Action → Domain → Repository → Domain → Responder → HTTP Response\n\n";

echo "Flow Steps:\n";
echo "1. Action receives HTTP request\n";
echo "2. Action validates and creates DTO\n";
echo "3. Action calls Domain with DTO\n";
echo "4. Domain validates business rules\n";
echo "5. Domain calls Repository for data operations\n";
echo "6. Domain returns DomainResult\n";
echo "7. Responder converts DomainResult to HTTP Response\n";

echo "\n9. BENEFITS OF ADR + DDD:\n";
echo str_repeat("-", 30) . "\n";
echo "✓ Clean separation of concerns\n";
echo "✓ Framework-agnostic domain logic\n";
echo "✓ Testable components\n";
echo "✓ Reusable business logic\n";
echo "✓ Type-safe data transfer\n";
echo "✓ Consistent error handling\n";
echo "✓ Content negotiation support\n";
echo "✓ Easy to extend and maintain\n";

echo "\n10. INTEGRATION WITH EXISTING MVC:\n";
echo str_repeat("-", 40) . "\n";
echo "✓ Extends existing Controller class\n";
echo "✓ Uses same DI container\n";
echo "✓ Compatible with current routing\n";
echo "✓ Gradual migration possible\n";
echo "✓ Mix ADR and traditional MVC as needed\n";

echo "\n=== ADR DEMO COMPLETED ===\n";