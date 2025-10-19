# EAV Quick Start Guide

## 5-Minute Setup

### Step 1: Run Migration (1 minute)

```bash
php cli.php migrate
```

This creates all EAV database tables.

### Step 2: Setup Entity Type (2 minutes)

```php
use Eav\Models\EntityType;
use Eav\Models\Attribute;

// Create entity type
$productType = new EntityType([
    'entity_type_code' => 'product',
    'entity_type_name' => 'Product',
    'is_active' => true
]);
$productType->save();

// Add attributes
$nameAttr = new Attribute([
    'entity_type_id' => $productType->id,
    'attribute_code' => 'name',
    'attribute_name' => 'Name',
    'backend_type' => 'varchar',
    'frontend_input' => 'text',
    'is_required' => true,
    'is_searchable' => true
]);
$nameAttr->save();

$priceAttr = new Attribute([
    'entity_type_id' => $productType->id,
    'attribute_code' => 'price',
    'attribute_name' => 'Price',
    'backend_type' => 'decimal',
    'frontend_input' => 'number',
    'is_required' => true,
    'is_filterable' => true
]);
$priceAttr->save();
```

### Step 3: Use the System (2 minutes)

```php
// Get services
$entityManager = $di->get('eavEntityManager');
$repository = $di->get('eavEntityRepository');

// Create entity
$product = $entityManager->create($productType->id, [
    'name' => 'Awesome Product',
    'price' => 99.99
]);

// Find entity
$found = $entityManager->find($product->id);
echo $found->attributeValues['name']; // "Awesome Product"

// Query entities
$expensive = $repository->query($productType->id)
    ->where('price', '>', 50)
    ->get();

// Update entity
$entityManager->update($product->id, [
    'price' => 79.99
]);
```

## Common Use Cases

### Product Catalog

```php
// Create attributes
$attributes = [
    ['code' => 'sku', 'name' => 'SKU', 'type' => 'varchar', 'required' => true],
    ['code' => 'name', 'name' => 'Name', 'type' => 'varchar', 'required' => true],
    ['code' => 'price', 'name' => 'Price', 'type' => 'decimal', 'required' => true],
    ['code' => 'stock', 'name' => 'Stock', 'type' => 'int'],
    ['code' => 'category', 'name' => 'Category', 'type' => 'varchar'],
];

foreach ($attributes as $attr) {
    $attribute = new Attribute([
        'entity_type_id' => $productType->id,
        'attribute_code' => $attr['code'],
        'attribute_name' => $attr['name'],
        'backend_type' => $attr['type'],
        'frontend_input' => 'text',
        'is_required' => $attr['required'] ?? false
    ]);
    $attribute->save();
}

// Create products
$products = [
    ['sku' => 'P001', 'name' => 'Product 1', 'price' => 10.00, 'stock' => 100],
    ['sku' => 'P002', 'name' => 'Product 2', 'price' => 20.00, 'stock' => 50],
    ['sku' => 'P003', 'name' => 'Product 3', 'price' => 30.00, 'stock' => 75],
];

foreach ($products as $data) {
    $entityManager->create($productType->id, $data);
}
```

### Customer Management

```php
// Create customer entity type
$customerType = new EntityType([
    'entity_type_code' => 'customer',
    'entity_type_name' => 'Customer',
    'is_active' => true
]);
$customerType->save();

// Add customer attributes
$attrs = [
    ['code' => 'email', 'type' => 'varchar', 'unique' => true],
    ['code' => 'first_name', 'type' => 'varchar'],
    ['code' => 'last_name', 'type' => 'varchar'],
    ['code' => 'phone', 'type' => 'varchar'],
    ['code' => 'total_orders', 'type' => 'int'],
    ['code' => 'total_spent', 'type' => 'decimal'],
];

foreach ($attrs as $attr) {
    $attribute = new Attribute([
        'entity_type_id' => $customerType->id,
        'attribute_code' => $attr['code'],
        'attribute_name' => ucfirst(str_replace('_', ' ', $attr['code'])),
        'backend_type' => $attr['type'],
        'frontend_input' => 'text',
        'is_unique' => $attr['unique'] ?? false
    ]);
    $attribute->save();
}
```

## Key Tips

### 1. Choose Right Backend Type
- `varchar` - Short text (≤255 chars)
- `text` - Long text
- `int` - Integers
- `decimal` - Money, measurements
- `datetime` - Dates and times

### 2. Use Indexing for Performance
```php
$indexManager = $di->get('eavIndexManager');
$indexManager->rebuildIndexes($entityTypeId);
```

### 3. Enable Caching (default: on)
Cache is enabled by default in config.php. Clear when needed:
```php
$cacheManager = $di->get('eavCacheManager');
$cacheManager->clear(); // Clear all
$cacheManager->invalidateEntity($entityId); // Clear specific entity
```

### 4. Use Batch Operations for Bulk Data
```php
$batchManager = $di->get('eavBatchManager');

// Instead of loop
$ids = $batchManager->batchCreate($entityTypeId, $arrayOfData);

// Much faster than individual creates
```

### 5. Query Optimization
```php
// Good - specific attributes
$products = $repository->withAttributes($entityTypeId, ['name', 'price']);

// Good - with limit
$products = $repository->query($entityTypeId)->limit(100)->get();

// Avoid - loading all without limit
$all = $repository->all($entityTypeId); // Can be slow with many entities
```

## Troubleshooting

### Slow Queries?
1. Check indexes: `$indexManager->getTableStats()`
2. Optimize tables: `$indexManager->optimizeTables()`
3. Reduce max joins in config
4. Use select() to load specific attributes only

### Cache Issues?
1. Clear cache: `$cacheManager->clear()`
2. Check stats: `$cacheManager->getStats()`
3. Clean expired: `$cacheManager->cleanExpired()`

### Orphaned Data?
```php
$indexManager->cleanOrphanedValues();
```

## Next Steps

- Read full documentation: `app/Eav/README.md`
- Check examples: `app/Eav/EXAMPLES.php`
- Review implementation: `app/Eav/IMPLEMENTATION_SUMMARY.md`

---

**You're ready to build flexible, dynamic data models!** 🚀
