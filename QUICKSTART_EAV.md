# EAV Library Quick Setup Guide

## Quick Start (5 Minutes)

### Step 1: Verify Installation

The EAV library is already integrated into the framework. Verify the service provider is registered in `bootstrap.php`:

```php
$di->register('\\Core\\Eav\\Provider\\EavServiceProvider');
```

### Step 2: Run Database Migration

Run the base migration to create EAV tables:

```bash
cd /data/workspace/new
php migrate.php
```

This creates:
- `eav_entity_type`
- `eav_attribute`
- `eav_value_varchar`
- `eav_value_int`
- `eav_value_decimal`
- `eav_value_datetime`
- `eav_value_text`

### Step 3: Initialize and Synchronize

Run the example script to initialize and test:

```bash
php examples/eav_example.php
```

Or use the integration test:

```bash
php tests/Core/Eav/EavIntegrationTest.php
```

### Step 4: Create Your Own Entity Type

1. Create configuration file `config/eav/myentity.php`:

```php
<?php
return [
    'label' => 'My Entity',
    'entity_table' => 'eav_entity_myentity',
    'storage_strategy' => 'eav',
    'attributes' => [
        [
            'code' => 'title',
            'label' => 'Title',
            'backend_type' => 'varchar',
            'frontend_type' => 'text',
            'is_required' => true,
            'is_searchable' => true,
            'sort_order' => 10
        ]
    ]
];
```

2. Synchronize in your code:

```php
<?php
require_once 'bootstrap.php';

use Core\Di\Container;

$di = Container::getDefault();
$schemaManager = $di->get('eav.schema_manager');
$registry = $di->get('eav.registry');

// Initialize base schema (if not already done)
$schemaManager->initialize();

// Synchronize your entity type
$myEntityType = $registry->get('myentity');
$schemaManager->synchronize($myEntityType);

echo "Entity type synchronized!\n";
```

### Step 5: Create and Manage Entities

```php
<?php
require_once 'bootstrap.php';

use Core\Di\Container;

$di = Container::getDefault();
$entityManager = $di->get('eav.entity_manager');
$registry = $di->get('eav.registry');
$repository = $di->get('eav.entity_repository');

$entityType = $registry->get('myentity');

// CREATE
$entity = $entityManager->create($entityType, [
    'title' => 'My First Entity'
]);
echo "Created entity ID: " . $entity->getId() . "\n";

// READ
$loaded = $entityManager->load($entityType, $entity->getId());
echo "Loaded: " . $loaded->get('title') . "\n";

// UPDATE
$loaded->set('title', 'Updated Title');
$entityManager->save($loaded);
echo "Updated!\n";

// QUERY
$results = $repository->search($entityType, 'Updated');
foreach ($results as $r) {
    echo "Found: " . $r->get('title') . "\n";
}

// DELETE
$entityManager->delete($loaded);
echo "Deleted!\n";
```

## Pre-configured Entity Types

The library includes two example entity types:

### Product Entity
- Configuration: `config/eav/product.php`
- Attributes: name, sku, description, price, quantity, is_active, created_date

### Customer Entity
- Configuration: `config/eav/customer.php`
- Attributes: first_name, last_name, email, phone, date_of_birth, address, is_verified

## Common Operations

### List All Entity Types

```php
$registry = $di->get('eav.registry');
$allTypes = $registry->all();

foreach ($allTypes as $code => $type) {
    echo "Entity Type: {$type->getLabel()} ({$code})\n";
    echo "  Attributes: " . $type->getAttributes()->count() . "\n";
}
```

### Get Attribute Metadata

```php
$attrRepository = $di->get('eav.attribute_repository');

$attributes = $attrRepository->findByEntityType('product');
foreach ($attributes as $attr) {
    echo "{$attr->getLabel()} ({$attr->getCode()}) - {$attr->getBackendType()}\n";
}
```

### Pagination

```php
$repository = $di->get('eav.entity_repository');
$productType = $registry->get('product');

$page1 = $repository->paginate($productType, [], 10, 1);
echo "Page 1 of {$page1['last_page']}\n";
echo "Total items: {$page1['total']}\n";

foreach ($page1['data'] as $product) {
    echo "- " . $product->get('name') . "\n";
}
```

## Troubleshooting

### Issue: Tables not created
**Solution**: Run `php migrate.php` or call `$schemaManager->initialize()`

### Issue: Attribute not found errors
**Solution**: Synchronize entity type with `$schemaManager->synchronize($entityType)`

### Issue: Validation errors
**Solution**: Check entity data matches attribute requirements (required, type, etc.)

### Issue: Performance issues with many entities
**Solution**: Use batch operations `loadMultiple()` and add indexes on searchable attributes

## Next Steps

1. Read the full documentation: `app/Core/Eav/README.md`
2. Review example implementations: `examples/eav_example.php`
3. Run integration tests: `tests/Core/Eav/EavIntegrationTest.php`
4. Create your custom entity types
5. Extend repositories for custom queries

## Resources

- Design Document: See the original Phase 2 design document
- Examples: `examples/eav_example.php`
- Tests: `tests/Core/Eav/EavIntegrationTest.php`
- README: `app/Core/Eav/README.md`

## Support

For detailed API documentation, see the inline comments in each class file.
