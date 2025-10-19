# EAV Library - Phase 2: Data Persistence Layer

## Overview

The EAV (Entity-Attribute-Value) Library provides a flexible, configuration-driven approach to managing dynamic entities with custom attributes. Phase 2 implements the complete data persistence layer, enabling full CRUD operations for entities and their attribute values.

## Features

### Phase 1 Foundation (Complete)
- ✅ Configuration system for entity types
- ✅ Entity, Attribute, and EntityType models
- ✅ Entity type registry
- ✅ Validation framework
- ✅ Exception hierarchy

### Phase 2 Persistence Layer (Complete)
- ✅ Database schema management
- ✅ Automated migration generation
- ✅ EAV table storage strategy
- ✅ Entity lifecycle management (CRUD)
- ✅ Value persistence across multiple backend types
- ✅ Repository pattern for queries
- ✅ Transaction safety
- ✅ Dirty tracking for optimized updates
- ✅ Batch operations

## Architecture

### Components

1. **Schema Management**
   - `SchemaManager`: Orchestrates schema creation and synchronization
   - `StructureBuilder`: Generates database table definitions
   - `MigrationGenerator`: Creates migration files

2. **Storage Layer**
   - `EavTableStorage`: EAV pattern implementation
   - `ValueTransformer`: Value type conversion
   - `StorageStrategyInterface`: Storage abstraction

3. **Entity Management**
   - `EntityManager`: Entity lifecycle operations
   - `ValueManager`: Attribute value coordination
   - `AttributeMetadataManager`: Metadata synchronization

4. **Repository Layer**
   - `EntityRepository`: Entity queries and filtering
   - `AttributeRepository`: Attribute metadata queries

## Database Schema

### Core Tables

- `eav_entity_type`: Entity type registry
- `eav_attribute`: Attribute metadata
- `eav_entity_{code}`: One table per entity type
- `eav_value_{type}`: Value tables (varchar, int, decimal, datetime, text)

## Installation

### 1. Register Service Provider

The EAV service provider is already registered in `bootstrap.php`:

```php
$di->register('\\Core\\Eav\\Provider\\EavServiceProvider');
```

### 2. Run Base Migration

```bash
php migrate.php
```

This creates the base EAV schema tables.

### 3. Configure Entity Types

Create entity type configuration files in `config/eav/{entity_code}.php`:

```php
// config/eav/product.php
return [
    'label' => 'Product',
    'entity_table' => 'eav_entity_product',
    'storage_strategy' => 'eav',
    'attributes' => [
        [
            'code' => 'name',
            'label' => 'Product Name',
            'backend_type' => 'varchar',
            'frontend_type' => 'text',
            'is_required' => true,
            'is_searchable' => true,
            'sort_order' => 10
        ],
        // ... more attributes
    ]
];
```

### 4. Synchronize Entity Types

```php
$schemaManager = $di->get('eav.schema_manager');
$registry = $di->get('eav.registry');

// Initialize base schema (if not already done)
$schemaManager->initialize();

// Synchronize entity type
$entityType = $registry->get('product');
$schemaManager->synchronize($entityType);
```

## Usage Examples

### Creating an Entity

```php
$entityManager = $di->get('eav.entity_manager');
$registry = $di->get('eav.registry');

$productType = $registry->get('product');

$product = $entityManager->create($productType, [
    'name' => 'Laptop',
    'sku' => 'LAPTOP-001',
    'price' => 1299.99,
    'quantity' => 10,
    'is_active' => 1
]);

echo "Created product with ID: " . $product->getId();
```

### Loading an Entity

```php
$product = $entityManager->load($productType, $productId);

if ($product) {
    echo "Name: " . $product->get('name');
    echo "Price: $" . $product->get('price');
}
```

### Updating an Entity

```php
$product = $entityManager->load($productType, $productId);

$product->set('price', 1199.99);
$product->set('quantity', 15);

// Only dirty attributes are saved
$entityManager->save($product);
```

### Deleting an Entity

```php
$product = $entityManager->load($productType, $productId);
$entityManager->delete($product);
```

### Querying Entities

```php
$repository = $di->get('eav.entity_repository');

// Find by attribute value
$products = $repository->findByAttribute($productType, 'sku', 'LAPTOP-001');

// Search by searchable attributes
$results = $repository->search($productType, 'laptop');

// Find all with options
$products = $repository->findAll($productType, [
    'limit' => 10,
    'offset' => 0,
    'order_by' => 'entity_id',
    'order_direction' => 'DESC'
]);

// Pagination
$paginated = $repository->paginate($productType, [], 20, 1);
echo "Total: " . $paginated['total'];
echo "Current page: " . $paginated['current_page'];
```

### Multiple Attributes Filter

```php
$products = $repository->findByAttributes($productType, [
    'is_active' => 1,
    'category' => 'Electronics'
]);
```

### Batch Operations

```php
// Load multiple entities
$ids = [1, 2, 3, 4, 5];
$products = $entityManager->loadMultiple($productType, $ids);

foreach ($products as $id => $product) {
    echo "Product {$id}: " . $product->get('name') . "\n";
}
```

## Backend Types

The library supports the following backend types for attribute values:

- **varchar**: Short text values (255 chars)
- **text**: Long text values
- **int**: Integer values
- **decimal**: Decimal numbers (12,4 precision)
- **datetime**: Date and time values

## Validation

Entity validation happens automatically before persistence:

```php
try {
    $entity = $entityManager->create($productType, [
        'description' => 'Missing required fields'
    ]);
} catch (\Core\Exception\ValidationException $e) {
    $errors = $e->getErrors();
    foreach ($errors as $field => $message) {
        echo "{$field}: {$message}\n";
    }
}
```

## Dirty Tracking

Entities track which attributes have been modified:

```php
$product = $entityManager->load($productType, $productId);

$product->set('price', 99.99);
$product->set('quantity', 20);

// Check if entity has changes
if ($product->isDirty()) {
    // Get list of modified attributes
    $dirtyAttrs = $product->getDirtyAttributes(); // ['price', 'quantity']
    
    // Get only dirty values
    $dirtyData = $product->getDirtyData();
    
    // Save only dirty attributes
    $entityManager->save($product);
}
```

## Transaction Safety

All multi-table operations are wrapped in database transactions:

```php
try {
    $db->beginTransaction();
    
    // Create entity record
    // Save attribute values
    
    $db->commit();
} catch (\Exception $e) {
    $db->rollback();
    throw $e;
}
```

## Performance Considerations

### Batch Loading
- Use `loadMultiple()` to load multiple entities efficiently
- Reduces queries from N to 1 per backend type

### Attribute Projection
- Only load needed attributes by filtering the AttributeCollection
- Minimizes joins to value tables

### Caching
- Attribute metadata is cached in memory
- Entity type registry caches configurations

### Indexes
- Value tables have indexes on (attribute_id, value) for searchable attributes
- Unique constraint on (entity_type_id, attribute_id, entity_id)

## Testing

### Run Integration Tests

```bash
php tests/Core/Eav/EavIntegrationTest.php
```

### Run Example

```bash
php examples/eav_example.php
```

## Error Handling

The library uses a comprehensive exception hierarchy:

- `EavException`: Base exception
- `EntityException`: Entity-related errors
- `ConfigurationException`: Configuration errors
- `StorageException`: Storage/persistence errors
- `SynchronizationException`: Schema sync errors
- `ValidationException`: Validation failures

Example:

```php
try {
    $entity = $entityManager->load($productType, $id);
} catch (\Core\Eav\Exception\EntityException $e) {
    echo "Entity error: " . $e->getMessage();
    echo "Context: " . json_encode($e->getContext());
}
```

## Configuration Options

### Attribute Configuration

```php
[
    'code' => 'attribute_code',           // Unique identifier
    'label' => 'Display Label',           // Human-readable name
    'backend_type' => 'varchar',          // Storage type
    'frontend_type' => 'text',            // Input type
    'is_required' => true,                // Required validation
    'is_unique' => false,                 // Uniqueness constraint
    'is_searchable' => true,              // Enable search
    'is_filterable' => true,              // Enable filtering
    'default_value' => null,              // Default value
    'validation_rules' => ['email'],      // Validation rules
    'sort_order' => 10                    // Display order
]
```

### Entity Type Configuration

```php
[
    'label' => 'Entity Type Label',
    'entity_table' => 'eav_entity_code',
    'storage_strategy' => 'eav',
    'attributes' => [ /* ... */ ]
]
```

## Best Practices

1. **Always use transactions** for operations affecting multiple tables
2. **Validate before persistence** using `$entity->validate()`
3. **Use dirty tracking** to optimize updates
4. **Batch load entities** when working with multiple records
5. **Cache attribute metadata** for frequently accessed entity types
6. **Index searchable attributes** for better query performance
7. **Use repositories** for complex queries instead of direct EntityManager usage

## Extending the Library

### Custom Storage Strategy

Implement `StorageStrategyInterface`:

```php
class CustomStorage implements StorageStrategyInterface
{
    public function loadValues(int $entityId, array $attributes): array
    {
        // Custom implementation
    }
    
    public function saveValues(int $entityId, int $entityTypeId, array $values): bool
    {
        // Custom implementation
    }
    
    // ... implement other methods
}
```

### Custom Repository Methods

Extend `EntityRepository`:

```php
class ProductRepository extends EntityRepository
{
    public function findActiveProducts()
    {
        return $this->findByAttribute($this->productType, 'is_active', 1);
    }
}
```

## Troubleshooting

### Schema not initialized
```
Error: Table 'eav_entity_type' doesn't exist
Solution: Run $schemaManager->initialize()
```

### Attribute missing ID
```
Error: Attribute 'name' does not have an ID
Solution: Ensure entity type is synchronized with $schemaManager->synchronize()
```

### Validation errors
```
Error: Entity validation failed
Solution: Check $e->getErrors() for details on which attributes failed
```

## License

This library is part of the core framework and follows the same license terms.

## Support

For issues, questions, or contributions, please refer to the main project documentation.
