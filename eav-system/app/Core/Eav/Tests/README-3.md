# EAV Test Suite

## Overview

This directory contains comprehensive tests for the EAV (Entity-Attribute-Value) module, including both unit tests and integration tests.

## Test Files

### Unit Tests

1. **StorageStrategyTest.php**
   - Tests all storage strategy implementations (varchar, int, decimal, text, datetime)
   - Validates type conversion and transformation
   - Tests validation logic for each backend type

2. **FilterTranslatorTest.php**
   - Tests SQL condition generation for various operators
   - Validates filter translation logic
   - Tests complex AND/OR filter combinations

3. **JoinOptimizerTest.php**
   - Tests join optimization strategies
   - Validates subquery fallback logic
   - Tests batch join generation

4. **CacheManagerTest.php**
   - Tests cache operations (get, set, delete)
   - Validates cache invalidation strategies
   - Tests memory cache functionality

### Integration Tests

5. **IntegrationTest.php**
   - End-to-end CRUD workflows
   - Complex query scenarios
   - Batch operation testing
   - Cache integration
   - Validation testing
   - Repository pattern testing
   - Index management
   - Entity copying

## Running Tests

### Prerequisites

1. PHPUnit installed (typically via Composer)
2. Database connection configured for testing
3. Test database created and migrations run

### Run All Tests

```bash
# From project root
vendor/bin/phpunit app/Eav/Tests/

# Or with specific configuration
vendor/bin/phpunit --configuration phpunit.xml app/Eav/Tests/
```

### Run Specific Test Suite

```bash
# Unit tests only
vendor/bin/phpunit app/Eav/Tests/StorageStrategyTest.php
vendor/bin/phpunit app/Eav/Tests/FilterTranslatorTest.php
vendor/bin/phpunit app/Eav/Tests/JoinOptimizerTest.php
vendor/bin/phpunit app/Eav/Tests/CacheManagerTest.php

# Integration tests (requires database)
vendor/bin/phpunit app/Eav/Tests/IntegrationTest.php
```

### Run with Coverage

```bash
vendor/bin/phpunit --coverage-html coverage/ app/Eav/Tests/
```

## Test Configuration

### PHPUnit Configuration Example

Create `phpunit.xml` in project root:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="bootstrap.php"
         colors="true"
         stopOnFailure="false">
    <testsuites>
        <testsuite name="EAV Unit Tests">
            <directory>app/Eav/Tests</directory>
            <exclude>app/Eav/Tests/IntegrationTest.php</exclude>
        </testsuite>
        <testsuite name="EAV Integration Tests">
            <file>app/Eav/Tests/IntegrationTest.php</file>
        </testsuite>
    </testsuites>
    <filter>
        <whitelist>
            <directory suffix=".php">app/Eav</directory>
            <exclude>
                <directory>app/Eav/Tests</directory>
            </exclude>
        </whitelist>
    </filter>
</phpunit>
```

### Database Setup for Integration Tests

1. Create test database:
```sql
CREATE DATABASE eav_test;
```

2. Configure test database connection in test bootstrap
3. Run migrations on test database
4. Integration tests will use this database

## Test Coverage

The test suite covers:

- ✅ **Storage Layer** - All 5 storage strategies
- ✅ **Query Layer** - Filter translation and join optimization
- ✅ **Cache Layer** - Memory and database caching
- ✅ **CRUD Operations** - Create, read, update, delete
- ✅ **Batch Operations** - Bulk create, update, delete
- ✅ **Complex Queries** - Filters, ranges, pagination
- ✅ **Validation** - Type validation, required fields
- ✅ **Repository Patterns** - firstOrCreate, updateOrCreate
- ✅ **Index Management** - Create, rebuild, optimize
- ✅ **Entity Operations** - Copy, soft delete

## Writing New Tests

### Unit Test Template

```php
<?php
namespace Eav\Tests;

use PHPUnit\Framework\TestCase;

class MyComponentTest extends TestCase
{
    protected function setUp(): void
    {
        // Setup test fixtures
    }

    public function testMyFeature()
    {
        // Arrange
        $component = new MyComponent();
        
        // Act
        $result = $component->doSomething();
        
        // Assert
        $this->assertEquals('expected', $result);
    }

    protected function tearDown(): void
    {
        // Cleanup
    }
}
```

### Integration Test Template

```php
<?php
namespace Eav\Tests;

use PHPUnit\Framework\TestCase;

class MyIntegrationTest extends TestCase
{
    protected $di;
    
    protected function setUp(): void
    {
        // Setup DI container and services
        $this->di = setupTestContainer();
    }

    public function testEndToEndWorkflow()
    {
        $service = $this->di->get('myService');
        
        // Test complete workflow
        $result = $service->completeWorkflow();
        
        $this->assertTrue($result);
    }
}
```

## Continuous Integration

### GitHub Actions Example

```yaml
name: EAV Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: eav_test
        ports:
          - 3306:3306
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: pdo, pdo_mysql
    
    - name: Install dependencies
      run: composer install
    
    - name: Run migrations
      run: php cli.php migrate --env=test
    
    - name: Run tests
      run: vendor/bin/phpunit app/Eav/Tests/
```

## Mocking

Tests use PHPUnit's mocking capabilities for external dependencies:

```php
$db = $this->createMock(\Core\Database\Database::class);
$db->method('table')->willReturn($queryBuilder);
```

## Best Practices

1. **Isolation** - Each test should be independent
2. **AAA Pattern** - Arrange, Act, Assert
3. **Descriptive Names** - Test names should describe what they test
4. **One Assert Per Test** - Prefer focused tests
5. **Mock External Dependencies** - Don't rely on external services
6. **Clean Up** - Use tearDown() to clean test data
7. **Test Edge Cases** - Include boundary conditions and error cases

## Troubleshooting

### Tests Failing

1. Check database connection
2. Ensure migrations are run
3. Verify test database is clean
4. Check PHPUnit version compatibility

### Skipped Tests

Integration tests are skipped by default if database is not configured. Set up test database to enable them.

### Performance

- Unit tests should run in < 1 second total
- Integration tests may take 10-30 seconds
- Use `--filter` to run specific tests during development

## Contributing

When adding new features to the EAV module:

1. Write unit tests for new components
2. Add integration tests for new workflows
3. Ensure all tests pass before submitting PR
4. Aim for >80% code coverage

---

**Test Count**: 30+ test methods  
**Coverage Goal**: 80%+  
**Run Time**: < 30 seconds
