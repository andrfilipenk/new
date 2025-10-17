<?php
// examples/eav_example.php
// Example demonstrating EAV Library usage

require_once __DIR__ . '/../bootstrap.php';

use Core\Di\Container;

$di = Container::getDefault();

// Get EAV services
$schemaManager = $di->get('eav.schema_manager');
$entityManager = $di->get('eav.entity_manager');
$registry = $di->get('eav.registry');
$repository = $di->get('eav.entity_repository');

echo "=== EAV Library Phase 2 Example ===\n\n";

try {
    // Step 1: Initialize EAV schema
    echo "1. Initializing EAV base schema...\n";
    $schemaManager->initialize();
    echo "   ✓ Base schema initialized\n\n";

    // Step 2: Synchronize entity type (product)
    echo "2. Synchronizing Product entity type...\n";
    $productType = $registry->get('product');
    $schemaManager->synchronize($productType);
    echo "   ✓ Product entity type synchronized\n";
    echo "   - Entity table: {$productType->getEntityTable()}\n";
    echo "   - Attributes: " . $productType->getAttributes()->count() . "\n\n";

    // Step 3: Create a product entity
    echo "3. Creating a new product...\n";
    $productData = [
        'name' => 'Laptop Computer',
        'sku' => 'LAPTOP-001',
        'description' => 'High-performance laptop with 16GB RAM',
        'price' => 1299.99,
        'quantity' => 25,
        'is_active' => 1,
        'created_date' => date('Y-m-d H:i:s')
    ];

    $product = $entityManager->create($productType, $productData);
    echo "   ✓ Product created with ID: {$product->getId()}\n";
    echo "   - Name: {$product->get('name')}\n";
    echo "   - SKU: {$product->get('sku')}\n";
    echo "   - Price: \${$product->get('price')}\n\n";

    // Step 4: Load the product
    echo "4. Loading product by ID...\n";
    $loadedProduct = $entityManager->load($productType, $product->getId());
    if ($loadedProduct) {
        echo "   ✓ Product loaded successfully\n";
        echo "   - Name: {$loadedProduct->get('name')}\n";
        echo "   - Quantity: {$loadedProduct->get('quantity')}\n\n";
    }

    // Step 5: Update the product
    echo "5. Updating product...\n";
    $loadedProduct->set('price', 1199.99);
    $loadedProduct->set('quantity', 30);
    echo "   - Dirty attributes: " . implode(', ', $loadedProduct->getDirtyAttributes()) . "\n";
    $entityManager->save($loadedProduct);
    echo "   ✓ Product updated successfully\n";
    echo "   - New price: \${$loadedProduct->get('price')}\n";
    echo "   - New quantity: {$loadedProduct->get('quantity')}\n\n";

    // Step 6: Create multiple products
    echo "6. Creating multiple products...\n";
    $products = [
        [
            'name' => 'Wireless Mouse',
            'sku' => 'MOUSE-001',
            'description' => 'Ergonomic wireless mouse',
            'price' => 29.99,
            'quantity' => 100,
            'is_active' => 1
        ],
        [
            'name' => 'USB Keyboard',
            'sku' => 'KEYB-001',
            'description' => 'Mechanical keyboard with RGB',
            'price' => 79.99,
            'quantity' => 50,
            'is_active' => 1
        ]
    ];

    $createdProducts = [];
    foreach ($products as $data) {
        $p = $entityManager->create($productType, $data);
        $createdProducts[] = $p;
        echo "   ✓ Created: {$p->get('name')} (ID: {$p->getId()})\n";
    }
    echo "\n";

    // Step 7: Find products by attribute
    echo "7. Finding products by SKU...\n";
    $foundProducts = $repository->findByAttribute($productType, 'sku', 'MOUSE-001');
    foreach ($foundProducts as $fp) {
        echo "   ✓ Found: {$fp->get('name')} - \${$fp->get('price')}\n";
    }
    echo "\n";

    // Step 8: Search products
    echo "8. Searching products by name...\n";
    $searchResults = $repository->search($productType, 'Mouse');
    foreach ($searchResults as $sr) {
        echo "   ✓ Found: {$sr->get('name')} (SKU: {$sr->get('sku')})\n";
    }
    echo "\n";

    // Step 9: Find all products
    echo "9. Listing all products...\n";
    $allProducts = $repository->findAll($productType, ['limit' => 10]);
    echo "   Found " . count($allProducts) . " products:\n";
    foreach ($allProducts as $p) {
        echo "   - {$p->get('name')} (\${$p->get('price')}) - Qty: {$p->get('quantity')}\n";
    }
    echo "\n";

    // Step 10: Paginate results
    echo "10. Paginating products...\n";
    $pagination = $repository->paginate($productType, [], 2, 1);
    echo "   Page {$pagination['current_page']} of {$pagination['last_page']}\n";
    echo "   Total products: {$pagination['total']}\n";
    echo "   Products on this page:\n";
    foreach ($pagination['data'] as $p) {
        echo "   - {$p->get('name')}\n";
    }
    echo "\n";

    // Step 11: Synchronize customer entity type
    echo "11. Synchronizing Customer entity type...\n";
    $customerType = $registry->get('customer');
    $schemaManager->synchronize($customerType);
    echo "   ✓ Customer entity type synchronized\n\n";

    // Step 12: Create a customer
    echo "12. Creating a new customer...\n";
    $customerData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'phone' => '+1234567890',
        'date_of_birth' => '1990-01-15',
        'address' => '123 Main St, New York, NY 10001',
        'is_verified' => 1
    ];

    $customer = $entityManager->create($customerType, $customerData);
    echo "   ✓ Customer created with ID: {$customer->getId()}\n";
    echo "   - Name: {$customer->get('first_name')} {$customer->get('last_name')}\n";
    echo "   - Email: {$customer->get('email')}\n\n";

    echo "=== All operations completed successfully! ===\n";

} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    if ($e instanceof \Core\Exception\BaseException) {
        echo "   Context: " . json_encode($e->getContext()) . "\n";
    }
}
