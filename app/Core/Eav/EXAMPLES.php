<?php
/**
 * EAV Module Usage Examples
 * 
 * This file demonstrates various use cases and patterns for the EAV module
 */

// Example 1: Setup Entity Type and Attributes
function setupProductCatalog($di)
{
    use Eav\Models\EntityType;
    use Eav\Models\Attribute;

    // Create entity type
    $productType = new EntityType([
        'entity_type_code' => 'product',
        'entity_type_name' => 'Product',
        'description' => 'Product catalog entities',
        'is_active' => true
    ]);
    $productType->save();

    // Define attributes
    $attributeDefinitions = [
        [
            'code' => 'sku',
            'name' => 'SKU',
            'type' => 'varchar',
            'input' => 'text',
            'required' => true,
            'unique' => true,
            'searchable' => true,
        ],
        [
            'code' => 'name',
            'name' => 'Product Name',
            'type' => 'varchar',
            'input' => 'text',
            'required' => true,
            'searchable' => true,
            'filterable' => true,
        ],
        [
            'code' => 'description',
            'name' => 'Description',
            'type' => 'text',
            'input' => 'textarea',
            'searchable' => true,
        ],
        [
            'code' => 'price',
            'name' => 'Price',
            'type' => 'decimal',
            'input' => 'number',
            'required' => true,
            'filterable' => true,
            'validation_rules' => ['min' => 0],
        ],
        [
            'code' => 'stock_quantity',
            'name' => 'Stock Quantity',
            'type' => 'int',
            'input' => 'number',
            'required' => true,
            'default_value' => '0',
            'validation_rules' => ['min' => 0],
        ],
        [
            'code' => 'category',
            'name' => 'Category',
            'type' => 'varchar',
            'input' => 'select',
            'filterable' => true,
        ],
        [
            'code' => 'is_featured',
            'name' => 'Is Featured',
            'type' => 'int',
            'input' => 'boolean',
            'default_value' => '0',
        ],
        [
            'code' => 'release_date',
            'name' => 'Release Date',
            'type' => 'datetime',
            'input' => 'date',
        ],
    ];

    foreach ($attributeDefinitions as $def) {
        $attribute = new Attribute([
            'entity_type_id' => $productType->id,
            'attribute_code' => $def['code'],
            'attribute_name' => $def['name'],
            'backend_type' => $def['type'],
            'frontend_input' => $def['input'],
            'is_required' => $def['required'] ?? false,
            'is_unique' => $def['unique'] ?? false,
            'is_searchable' => $def['searchable'] ?? false,
            'is_filterable' => $def['filterable'] ?? false,
            'default_value' => $def['default_value'] ?? null,
            'validation_rules' => json_encode($def['validation_rules'] ?? []),
            'sort_order' => $def['sort_order'] ?? 0,
        ]);
        $attribute->save();
    }

    return $productType;
}

// Example 2: CRUD Operations
function crudOperations($di)
{
    $entityManager = $di->get('eavEntityManager');
    $repository = $di->get('eavEntityRepository');

    $entityTypeId = 1; // Product type

    // CREATE
    $product = $entityManager->create($entityTypeId, [
        'entity_code' => 'PROD-001',
        'sku' => 'WIDGET-PRO-001',
        'name' => 'Premium Widget',
        'description' => 'High-quality professional widget',
        'price' => 99.99,
        'stock_quantity' => 100,
        'category' => 'Electronics',
        'is_featured' => 1,
        'release_date' => '2024-01-15 10:00:00',
        'is_active' => true
    ]);

    echo "Created product ID: {$product->id}\n";

    // READ
    $loadedProduct = $entityManager->find($product->id);
    echo "Product name: {$loadedProduct->attributeValues['name']}\n";
    echo "Product price: {$loadedProduct->attributeValues['price']}\n";

    // UPDATE
    $entityManager->update($product->id, [
        'price' => 89.99,
        'stock_quantity' => 95
    ]);

    // DELETE (soft)
    $entityManager->delete($product->id, true);

    // Restore is handled at Entity level
    $entity = \Eav\Models\Entity::find($product->id);
    $entity->restore();
}

// Example 3: Advanced Queries
function advancedQueries($di)
{
    $repository = $di->get('eavEntityRepository');
    $entityTypeId = 1; // Product type

    // Simple filter
    $electronics = $repository->findByAttribute($entityTypeId, 'category', 'Electronics');

    // Price range
    $affordableProducts = $repository->query($entityTypeId)
        ->where('price', '>=', 10)
        ->where('price', '<=', 50)
        ->orderBy('price', 'ASC')
        ->get();

    // Multiple conditions
    $featuredInStock = $repository->query($entityTypeId)
        ->where('is_featured', '=', 1)
        ->where('stock_quantity', '>', 0)
        ->orderBy('name', 'ASC')
        ->limit(20)
        ->get();

    // LIKE search
    $widgetProducts = $repository->searchLike($entityTypeId, 'name', 'Widget', 50);

    // IN clause
    $categorizedProducts = $repository->whereIn(
        $entityTypeId,
        'category',
        ['Electronics', 'Tools', 'Gadgets']
    );

    // BETWEEN clause
    $midRangeProducts = $repository->whereBetween($entityTypeId, 'price', 25, 75);

    // Complex query with pagination
    $page = 1;
    $perPage = 20;

    $result = $repository->paginate($entityTypeId, $page, $perPage);
    
    foreach ($result['data'] as $product) {
        echo "{$product->attributeValues['name']} - \${$product->attributeValues['price']}\n";
    }

    echo "Page {$result['page']} of {$result['total_pages']}\n";
    echo "Total products: {$result['total']}\n";
}

// Example 4: Batch Operations
function batchOperations($di)
{
    $batchManager = $di->get('eavBatchManager');
    $entityTypeId = 1;

    // Batch create
    $productsData = [];
    for ($i = 1; $i <= 100; $i++) {
        $productsData[] = [
            'entity_code' => "BATCH-{$i}",
            'sku' => "SKU-BATCH-{$i}",
            'name' => "Batch Product {$i}",
            'price' => rand(10, 100),
            'stock_quantity' => rand(0, 500),
            'category' => ['Electronics', 'Tools', 'Gadgets'][rand(0, 2)],
            'is_active' => true
        ];
    }

    $entityIds = $batchManager->batchCreate($entityTypeId, $productsData);
    echo "Created " . count($entityIds) . " products\n";

    // Batch update values
    $attributeRepository = $di->get('eavAttributeRepository');
    $priceAttr = $attributeRepository->findByCode('price', $entityTypeId);

    $updates = [];
    foreach ($entityIds as $id) {
        $updates[] = [
            'entity_id' => $id,
            'attribute' => $priceAttr,
            'value' => rand(10, 100) * 0.99 // Apply 1% discount
        ];
    }

    $batchManager->batchUpdateValues($updates);

    // Batch delete
    $toDelete = array_slice($entityIds, 0, 10);
    $deletedCount = $batchManager->batchDelete($toDelete, true);
    echo "Deleted {$deletedCount} products\n";

    // Batch copy
    $toCopy = array_slice($entityIds, 10, 5);
    $copied = $batchManager->batchCopy($toCopy);
    echo "Copied " . count($copied) . " products\n";
}

// Example 5: Cache Management
function cacheManagement($di)
{
    $cacheManager = $di->get('eavCacheManager');

    // Get cache statistics
    $stats = $cacheManager->getStats();
    echo "Total cache entries: {$stats['total_entries']}\n";
    echo "Active entries: {$stats['active_entries']}\n";
    echo "Expired entries: {$stats['expired_entries']}\n";

    // Clear expired cache
    $cleaned = $cacheManager->cleanExpired();
    echo "Cleaned {$cleaned} expired entries\n";

    // Clear specific entity cache
    $cacheManager->invalidateEntity(123);

    // Clear entity type cache
    $cacheManager->invalidateEntityType(1);

    // Clear all query cache
    $cacheManager->clear('query:*');

    // Use remember pattern
    $value = $cacheManager->remember('expensive:operation', function() {
        // Expensive operation
        return calculateComplexData();
    }, 3600);
}

// Example 6: Index Management
function indexManagement($di)
{
    $indexManager = $di->get('eavIndexManager');
    $attributeRepository = $di->get('eavAttributeRepository');

    $entityTypeId = 1;

    // Get searchable attributes
    $searchableAttrs = $attributeRepository->getSearchableAttributes($entityTypeId);

    // Create indexes for searchable attributes
    foreach ($searchableAttrs as $attr) {
        $indexManager->createAttributeIndex($attr->id, $attr->backend_type);
        echo "Created index for {$attr->attribute_code}\n";
    }

    // Rebuild all indexes
    $indexManager->rebuildIndexes($entityTypeId);

    // Optimize tables
    $indexManager->optimizeTables();

    // Analyze tables for query optimization
    $indexManager->analyzeTables();

    // Get table statistics
    $stats = $indexManager->getTableStats();
    foreach ($stats as $table => $stat) {
        echo "{$table}: {$stat['row_count']} rows\n";
    }

    // Clean orphaned values
    $cleaned = $indexManager->cleanOrphanedValues();
    echo "Cleaned {$cleaned} orphaned values\n";
}

// Example 7: Repository Patterns
function repositoryPatterns($di)
{
    $repository = $di->get('eavEntityRepository');
    $entityTypeId = 1;

    // First or Create
    $product = $repository->firstOrCreate(
        $entityTypeId,
        ['sku' => 'UNIQUE-SKU-001'], // Search criteria
        ['name' => 'New Product', 'price' => 29.99] // Additional data for create
    );

    // Update or Create
    $updated = $repository->updateOrCreate(
        $entityTypeId,
        ['sku' => 'UNIQUE-SKU-002'],
        ['name' => 'Updated Product', 'price' => 39.99]
    );

    // Get or create with custom logic
    $entities = $repository->search($entityTypeId, ['sku' => 'CUSTOM-001']);
    if (empty($entities)) {
        $entity = $repository->create($entityTypeId, [
            'sku' => 'CUSTOM-001',
            'name' => 'Custom Product'
        ]);
    } else {
        $entity = $entities[0];
    }

    // Bulk operations
    $ids = [1, 2, 3, 4, 5];
    $updated = $repository->bulkUpdate($ids, ['is_featured' => 1]);
    echo "Updated {$updated} products\n";

    $deleted = $repository->bulkDelete([6, 7, 8], true);
    echo "Deleted {$deleted} products\n";

    // Get children
    $parentId = 10;
    $children = $repository->getChildren($parentId);
    echo "Found " . count($children) . " child products\n";

    // Get active only
    $activeProducts = $repository->getActive($entityTypeId, 100);
}

// Example 8: Working with Attribute Options
function attributeOptions($di)
{
    use Eav\Models\Attribute;
    use Eav\Models\AttributeOption;

    $attributeRepository = $di->get('eavAttributeRepository');

    // Create select attribute
    $categoryAttr = new Attribute([
        'entity_type_id' => 1,
        'attribute_code' => 'category',
        'attribute_name' => 'Category',
        'backend_type' => 'varchar',
        'frontend_input' => 'select',
        'is_filterable' => true,
    ]);
    $categoryAttr->save();

    // Add options
    $categories = [
        'Electronics',
        'Tools',
        'Gadgets',
        'Accessories',
        'Furniture'
    ];

    foreach ($categories as $index => $category) {
        $option = new AttributeOption([
            'attribute_id' => $categoryAttr->id,
            'option_value' => strtolower(str_replace(' ', '_', $category)),
            'option_label' => $category,
            'sort_order' => $index * 10
        ]);
        $option->save();
    }

    // Load attribute with options
    $attr = Attribute::with(['options'])->find($categoryAttr->id);
    foreach ($attr->options as $option) {
        echo "{$option->option_label} ({$option->option_value})\n";
    }
}

// Helper function placeholder
function calculateComplexData()
{
    return ['result' => 'complex data'];
}
