# EAV (Entity-Attribute-Value) Library

## Overview

The EAV library provides a flexible, high-performance data modeling pattern that enables dynamic attribute management for entities without schema modifications. This implementation is designed for the existing PHP framework and follows the project's architectural patterns.

## Features

- **Dynamic Schema Management**: Define and modify entity attributes through configuration files without database migrations
- **Type Safety**: Strong type system with validation and casting capabilities
- **Flexible Storage**: Support for both EAV table storage and flat table storage strategies
- **Configuration-Driven**: Simple PHP array configuration for all aspects of the system
- **Extensible**: Modular architecture with clear separation of concerns

## Implementation Status

### ✅ Completed Components

1. **Module Structure**
   - `Module.php` - EAV module bootstrap
   - `config.php` - Module configuration with global EAV settings

2. **Exception Hierarchy**
   - `EavException` - Base exception class
   - `ConfigurationException` - Configuration-related errors
   - `SynchronizationException` - Schema synchronization errors
   - `ValidationException` - Attribute validation errors
   - `StorageException` - Storage operation errors
   - `EntityException` - Entity operation errors

3. **Entity Type System**
   - `Attribute` - Attribute model with validation and type casting
   - `AttributeCollection` - Collection of attributes with filtering capabilities
   - `EntityType` - Entity type model with attribute management
   - `Entity` - Entity instance with dirty tracking and validation

4. **Configuration System**
   - `ConfigLoader` - Loads and validates entity configurations from files
   - `EntityTypeRegistry` - Runtime registry of entity types

5. **Example Configurations**
   - `product.php` - Product entity with 17 attributes
   - `customer.php` - Customer entity with 18 attributes
   - `category.php` - Category entity with 17 attributes

6. **Service Provider**
   - `EavServiceProvider` - Registers EAV services in DI container

### 📋 Planned Components (To Be Implemented)

The following components are defined in the design document but not yet implemented:

1. **Schema Synchronization Engine**
   - Schema analyzer and comparator
   - Structure builder for database tables
   - Migration generator
   - Backup and restore functionality

2. **Storage Strategy Pattern**
   - `StorageStrategyInterface` - Interface for storage strategies
   - `EavTableStorage` - EAV table-based storage implementation
   - `FlatTableStorage` - Flat table storage implementation
   - Storage factory and selector

3. **Attribute Manager**
   - Attribute metadata CRUD operations
   - Attribute caching
   - Validation rule management

4. **Value Manager**
   - Value persistence and retrieval
   - Batch value operations
   - Value change tracking

5. **EAV Query Builder**
   - Extension of existing QueryBuilder
   - Join optimization for EAV queries
   - Filter translation for attributes
   - Query result caching

6. **Entity Manager**
   - Complete entity lifecycle management
   - Entity CRUD operations with attribute handling
   - Lazy and eager loading strategies
   - Entity validation and persistence

7. **Index Management**
   - Index creation based on attribute flags
   - Index synchronization
   - Performance monitoring

8. **Cache Strategy**
   - Multi-level caching
   - Cache invalidation logic
   - Query result caching

## Directory Structure

```
app/Eav/
├── Cache/                      # (Planned) Cache implementations
├── Config/                     # Configuration management
│   ├── entities/              # Entity configuration files
│   │   ├── product.php        # Product entity configuration
│   │   ├── customer.php       # Customer entity configuration
│   │   └── category.php       # Category entity configuration
│   ├── ConfigLoader.php       # Configuration file loader
│   └── EntityTypeRegistry.php # Entity type registry
├── Exception/                  # Exception hierarchy
│   ├── EavException.php       # Base exception
│   ├── ConfigurationException.php
│   ├── EntityException.php
│   ├── StorageException.php
│   ├── SynchronizationException.php
│   └── ValidationException.php
├── Model/                      # Core models
│   ├── Attribute.php          # Attribute model
│   ├── AttributeCollection.php # Attribute collection
│   ├── Entity.php             # Entity instance
│   └── EntityType.php         # Entity type model
├── Provider/                   # Service providers
│   └── EavServiceProvider.php # DI service registration
├── Query/                      # (Planned) Query builders
├── Schema/                     # (Planned) Schema management
├── Storage/                    # (Planned) Storage strategies
├── Module.php                  # Module bootstrap
├── config.php                  # Module configuration
└── README.md                   # This file
```

## Configuration

### Global Configuration

Edit `app/Eav/config.php` to configure global EAV settings:

```php
'eav' => [
    'table_prefix' => 'eav_',           // Prefix for all EAV tables
    'auto_sync' => true,                // Automatic schema synchronization
    'sync_mode' => 'immediate',         // immediate, deferred, or manual
    'backup_before_sync' => false,      // Create backup before sync
    'max_index_length' => 767,          // Maximum index key length
    'use_flat_tables' => true,          // Enable flat table generation
    'flat_table_threshold' => 10,       // Min attributes for flat tables
    'enable_cache' => true,             // Enable query result caching
    'cache_ttl' => 3600,                // Default cache TTL in seconds
    'config_path' => __DIR__ . '/Config/entities',
],
```

### Entity Configuration

Create entity configuration files in `app/Eav/Config/entities/`:

```php
<?php
// app/Eav/Config/entities/my_entity.php
return [
    'entity_code' => 'my_entity',
    'entity_label' => 'My Entity',
    'entity_table' => 'eav_my_entity_entity',
    'storage_strategy' => 'eav',
    'enable_cache' => true,
    'cache_ttl' => 3600,

    'attributes' => [
        [
            'attribute_code' => 'name',
            'attribute_label' => 'Name',
            'backend_type' => 'varchar',
            'frontend_type' => 'text',
            'is_required' => true,
            'is_unique' => false,
            'is_searchable' => true,
            'is_filterable' => true,
            'is_comparable' => true,
            'default_value' => null,
            'validation_rules' => [
                'min_length' => 3,
                'max_length' => 255,
            ],
            'sort_order' => 10,
        ],
        // More attributes...
    ],
];
```

## Usage Examples

### Loading Entity Types

```php
// Get entity type registry from DI container
$registry = $di->get('eav.entity_type_registry');

// Load an entity type
$productType = $registry->getByCode('product');

// Get all attributes
$attributes = $productType->getAttributes();

// Get specific attribute
$nameAttr = $productType->getAttribute('name');
```

### Creating and Validating Entities

```php
// Create a new entity instance
$product = new \Eav\Model\Entity($productType);

// Set attribute values
$product->setDataValue('name', 'Sample Product');
$product->setDataValue('sku', 'PROD-001');
$product->setDataValue('price', 99.99);

// Validate entity
try {
    $product->validate();
} catch (\Eav\Exception\ValidationException $e) {
    $errors = $e->getValidationErrors();
    // Handle validation errors
}

// Check dirty attributes
if ($product->isDirty()) {
    $dirtyData = $product->getDirtyData();
    // Save only changed attributes
}
```

### Working with Attributes

```php
// Get attribute
$attribute = $productType->getAttribute('price');

// Validate value
try {
    $attribute->validate(99.99);
} catch (\Eav\Exception\ValidationException $e) {
    // Handle validation error
}

// Cast value to appropriate type
$castedValue = $attribute->cast('99.99'); // Returns float 99.99

// Get attribute properties
$isRequired = $attribute->isRequired();
$isSearchable = $attribute->isSearchable();
$backendType = $attribute->getBackendType();
```

### Working with Attribute Collections

```php
$attributes = $productType->getAttributes();

// Get searchable attributes
$searchableAttrs = $attributes->getSearchable();

// Get filterable attributes
$filterableAttrs = $attributes->getFilterable();

// Get attributes by backend type
$varcharAttrs = $attributes->getByBackendType('varchar');

// Sort attributes
$attributes->sort();

// Iterate through attributes
foreach ($attributes as $code => $attribute) {
    echo $attribute->getAttributeLabel();
}
```

## Architecture

The EAV library follows a layered architecture:

1. **Configuration Layer**: Loads and validates entity definitions from PHP files
2. **Model Layer**: Provides entity types, attributes, and entity instances
3. **Storage Layer**: (Planned) Handles persistence with multiple strategies
4. **Query Layer**: (Planned) Extends QueryBuilder for EAV-specific queries
5. **Schema Layer**: (Planned) Manages database structure synchronization
6. **Cache Layer**: (Planned) Implements multi-level caching

## Validation Rules

Supported validation rules in attribute configuration:

- `min`: Minimum numeric value
- `max`: Maximum numeric value
- `min_length`: Minimum string length
- `max_length`: Maximum string length
- `pattern`: Regular expression pattern

Example:
```php
'validation_rules' => [
    'min' => 0,
    'max' => 999999,
    'min_length' => 3,
    'max_length' => 255,
    'pattern' => '/^[A-Z0-9-]+$/',
],
```

## Backend Types

Supported backend types for attribute storage:

- `varchar`: Short text values (up to 255 characters)
- `text`: Long text values
- `int`: Integer values
- `decimal`: Decimal/float values
- `datetime`: Date and time values

## Frontend Types

Supported frontend input types:

- `text`: Single-line text input
- `textarea`: Multi-line text input
- `select`: Single-selection dropdown
- `multiselect`: Multiple-selection dropdown
- `date`: Date picker
- `datetime`: Date and time picker
- `boolean`: Checkbox/toggle
- `number`: Numeric input

## Extension Points

The EAV library is designed to be extensible:

1. **Custom Attribute Types**: Extend `Attribute` class for custom backend/frontend types
2. **Custom Validation**: Add custom validation rules
3. **Custom Storage**: Implement `StorageStrategyInterface` (when available)
4. **Source Models**: Provide option sources for select/multiselect attributes
5. **Backend Models**: Custom backend processing for attribute values
6. **Frontend Models**: Custom frontend rendering for attributes

## Testing

(To be implemented)

Unit tests and integration tests will be created in:
- `tests/Unit/Eav/` - Unit tests for individual components
- `tests/Integration/Eav/` - Integration tests for EAV operations

## Performance Considerations

1. **Attribute Indexing**: Mark frequently filtered/searched attributes with appropriate flags
2. **Cache Configuration**: Adjust `cache_ttl` based on data change frequency
3. **Storage Strategy**: Use flat tables for entities with many consistent attributes
4. **Lazy Loading**: Entity attributes can be loaded on-demand
5. **Batch Operations**: (Planned) Use batch methods for bulk operations

## Future Enhancements

1. Complete implementation of planned components
2. Add support for attribute sets/groups
3. Implement attribute options/source models
4. Add support for scoped attributes (store, website, global)
5. Implement attribute value versioning
6. Add migration tools for schema changes
7. Performance monitoring and query profiling
8. Admin interface for entity/attribute management

## Contributing

When extending the EAV library:

1. Follow existing naming conventions
2. Add appropriate exception handling
3. Include validation for user inputs
4. Document public methods and classes
5. Write unit tests for new functionality
6. Update this README with new features

## License

This EAV library is part of the project framework and follows the same license terms.
