# EAV Schema Management Tests

## Overview

This directory contains tests for the EAV Schema Management system (Phase 5).

## Test Structure

```
tests/Eav/Schema/
├── SchemaDifferenceTest.php              # Unit tests for SchemaDifference
├── DifferenceSetTest.php                 # Unit tests for DifferenceSet
├── SchemaAnalysisReportTest.php          # Unit tests for SchemaAnalysisReport
├── SyncOptionsTest.php                   # Unit tests for SyncOptions
├── SchemaConfigTest.php                  # Unit tests for SchemaConfig
├── Integration/
│   └── SchemaManagementIntegrationTest.php  # End-to-end integration tests
└── README.md                             # This file
```

## Running Tests

### Run All Tests
```bash
php vendor/bin/phpunit tests/Eav/Schema/
```

### Run Specific Test Class
```bash
php vendor/bin/phpunit tests/Eav/Schema/SchemaDifferenceTest.php
```

### Run Unit Tests Only
```bash
php vendor/bin/phpunit tests/Eav/Schema/ --exclude-group integration
```

### Run Integration Tests Only
```bash
php vendor/bin/phpunit tests/Eav/Schema/ --group integration
```

## Test Coverage

### Unit Tests (Complete)

✅ **SchemaDifferenceTest** - Tests for individual schema differences
- Difference creation
- Risk score calculation
- Destructive operation detection
- Array conversion

✅ **DifferenceSetTest** - Tests for collections of differences
- Adding differences
- Filtering by action
- Destructive difference detection
- Risk score accumulation

✅ **SchemaAnalysisReportTest** - Tests for analysis reports
- Report creation
- Adding differences
- Risk level determination
- Recommendations

✅ **SyncOptionsTest** - Tests for synchronization options
- Strategy selection
- Dry run mode
- Force mode
- Backup configuration

✅ **SchemaConfigTest** - Tests for configuration management
- Default configuration
- Custom configuration
- Getters and setters
- Configuration loading

### Integration Tests (Placeholders)

⚠️ **SchemaManagementIntegrationTest** - End-to-end workflow tests
- Schema analysis workflow
- Synchronization workflow
- Backup/restore workflow
- Migration generation workflow
- Complete schema management cycle

*Note: Integration tests are placeholders and require database setup to run.*

## Writing New Tests

### Unit Test Template

```php
<?php

namespace Tests\Eav\Schema;

use PHPUnit\Framework\TestCase;

class YourComponentTest extends TestCase
{
    public function testYourFeature(): void
    {
        // Arrange
        $component = new YourComponent();
        
        // Act
        $result = $component->doSomething();
        
        // Assert
        $this->assertEquals($expected, $result);
    }
}
```

### Integration Test Template

```php
<?php

namespace Tests\Eav\Schema\Integration;

use PHPUnit\Framework\TestCase;

class YourIntegrationTest extends TestCase
{
    /**
     * @group integration
     */
    public function testYourWorkflow(): void
    {
        // Set up test database
        // Execute workflow
        // Verify results
        // Clean up
    }
}
```

## Test Database Setup

For integration tests, you need to:

1. Create a test database
2. Configure test database connection in `phpunit.xml`
3. Run migrations on test database
4. Ensure proper cleanup after tests

Example `phpunit.xml` configuration:

```xml
<phpunit>
    <php>
        <env name="DB_CONNECTION" value="mysql"/>
        <env name="DB_HOST" value="localhost"/>
        <env name="DB_DATABASE" value="eav_test"/>
        <env name="DB_USERNAME" value="root"/>
        <env name="DB_PASSWORD" value=""/>
    </php>
</phpunit>
```

## Test Coverage Goals

- **Unit Tests**: 90%+ coverage for core logic
- **Integration Tests**: All major workflows
- **Edge Cases**: Critical error handling paths

## Continuous Integration

Tests should be run:
- Before every commit (pre-commit hook)
- In CI/CD pipeline (on every push)
- Before merging pull requests
- Before production deployment

## Troubleshooting

### Tests Fail with "Class not found"
Ensure autoloader is properly configured:
```bash
composer dump-autoload
```

### Integration Tests Skip
Integration tests are marked as skipped by default. They require:
- Test database setup
- Proper configuration
- Remove `markTestSkipped()` calls

### PHPUnit Not Found
Install PHPUnit via composer:
```bash
composer require --dev phpunit/phpunit
```

## Contributing

When adding new features to Schema Management:
1. Write unit tests first (TDD approach)
2. Ensure tests pass
3. Aim for high code coverage
4. Add integration tests for workflows
5. Update this README if needed

## Resources

- [PHPUnit Documentation](https://phpunit.de/)
- [Schema Management Documentation](../../../app/Eav/PHASE5_IMPLEMENTATION.md)
- [Quick Start Guide](../../../app/Eav/PHASE5_QUICKSTART.md)
