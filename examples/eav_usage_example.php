<?php
// examples/eav_usage_example.php

/**
 * EAV Library Usage Examples
 * 
 * This file demonstrates how to use the EAV library components.
 * Note: This requires the bootstrap and DI container to be initialized.
 */

// For standalone usage, you would need to include bootstrap
// require_once __DIR__ . '/../bootstrap.php';

use Eav\Config\ConfigLoader;
use Eav\Config\EntityTypeRegistry;
use Eav\Model\Entity;
use Eav\Exception\ValidationException;
use Eav\Exception\ConfigurationException;

echo "=== EAV Library Usage Examples ===\n\n";

// Example 1: Loading Entity Configurations
echo "Example 1: Loading Entity Configurations\n";
echo str_repeat("-", 50) . "\n";

try {
    $configPath = __DIR__ . '/../app/Eav/Config/entities';
    $configLoader = new ConfigLoader($configPath);
    
    // Load product entity configuration
    $productType = $configLoader->load('product');
    
    echo "Entity Type: {$productType->getEntityLabel()}\n";
    echo "Entity Code: {$productType->getEntityCode()}\n";
    echo "Table Name: {$productType->getEntityTable()}\n";
    echo "Storage Strategy: {$productType->getStorageStrategy()}\n";
    echo "Total Attributes: {$productType->getAttributes()->count()}\n";
    echo "\n";
    
} catch (ConfigurationException $e) {
    echo "Configuration Error: " . $e->getMessage() . "\n\n";
}

// Example 2: Working with Entity Type Registry
echo "Example 2: Using Entity Type Registry\n";
echo str_repeat("-", 50) . "\n";

try {
    $configPath = __DIR__ . '/../app/Eav/Config/entities';
    $configLoader = new ConfigLoader($configPath);
    $registry = new EntityTypeRegistry($configLoader);
    
    // Get all entity types
    echo "Available Entity Types:\n";
    foreach ($registry->getCodes() as $code) {
        $entityType = $registry->getByCode($code);
        echo "  - {$entityType->getEntityLabel()} ({$code})\n";
    }
    echo "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Example 3: Exploring Attributes
echo "Example 3: Exploring Product Attributes\n";
echo str_repeat("-", 50) . "\n";

try {
    $configPath = __DIR__ . '/../app/Eav/Config/entities';
    $configLoader = new ConfigLoader($configPath);
    $productType = $configLoader->load('product');
    $attributes = $productType->getAttributes();
    
    echo "Searchable Attributes:\n";
    foreach ($attributes->getSearchable() as $attr) {
        echo "  - {$attr->getAttributeLabel()} ({$attr->getAttributeCode()})\n";
    }
    echo "\n";
    
    echo "Filterable Attributes:\n";
    foreach ($attributes->getFilterable() as $attr) {
        echo "  - {$attr->getAttributeLabel()} ({$attr->getAttributeCode()})\n";
    }
    echo "\n";
    
    echo "Required Attributes:\n";
    foreach ($attributes->getRequired() as $attr) {
        echo "  - {$attr->getAttributeLabel()} ({$attr->getAttributeCode()})\n";
    }
    echo "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Example 4: Creating and Validating Entities
echo "Example 4: Creating and Validating Product Entity\n";
echo str_repeat("-", 50) . "\n";

try {
    $configPath = __DIR__ . '/../app/Eav/Config/entities';
    $configLoader = new ConfigLoader($configPath);
    $productType = $configLoader->load('product');
    
    // Create a new product entity
    $product = new Entity($productType);
    
    // Set attribute values
    $product->setDataValue('name', 'Sample Product');
    $product->setDataValue('sku', 'PROD-001');
    $product->setDataValue('price', 99.99);
    $product->setDataValue('quantity', 100);
    $product->setDataValue('status', 1);
    $product->setDataValue('visibility', 4);
    
    // Validate the entity
    $product->validate();
    
    echo "✓ Product entity created and validated successfully!\n";
    echo "Product Data:\n";
    foreach ($product->getData() as $key => $value) {
        echo "  {$key}: {$value}\n";
    }
    echo "\n";
    
} catch (ValidationException $e) {
    echo "✗ Validation failed:\n";
    foreach ($e->getValidationErrors() as $field => $errors) {
        echo "  {$field}: " . implode(', ', $errors) . "\n";
    }
    echo "\n";
}

// Example 5: Validation Errors
echo "Example 5: Demonstrating Validation Errors\n";
echo str_repeat("-", 50) . "\n";

try {
    $configPath = __DIR__ . '/../app/Eav/Config/entities';
    $configLoader = new ConfigLoader($configPath);
    $productType = $configLoader->load('product');
    
    // Create entity with invalid data
    $product = new Entity($productType);
    $product->setDataValue('name', 'AB'); // Too short (min 3 chars)
    $product->setDataValue('sku', 'invalid sku'); // Invalid pattern
    // Missing required fields: price, quantity, status, visibility
    
    $product->validate();
    
} catch (ValidationException $e) {
    echo "Expected validation errors:\n";
    foreach ($e->getValidationErrors() as $field => $errors) {
        echo "  {$field}: " . implode(', ', $errors) . "\n";
    }
    echo "\n";
}

// Example 6: Dirty Tracking
echo "Example 6: Dirty Tracking for Efficient Updates\n";
echo str_repeat("-", 50) . "\n";

try {
    $configPath = __DIR__ . '/../app/Eav/Config/entities';
    $configLoader = new ConfigLoader($configPath);
    $productType = $configLoader->load('product');
    
    $product = new Entity($productType);
    $product->setDataValue('name', 'Original Name');
    $product->setDataValue('sku', 'PROD-001');
    $product->setDataValue('price', 99.99);
    
    // Mark as clean (simulating loaded from database)
    $product->markClean();
    
    echo "Is entity dirty? " . ($product->isDirty() ? "Yes" : "No") . "\n";
    
    // Modify some values
    $product->setDataValue('name', 'Updated Name');
    $product->setDataValue('price', 149.99);
    
    echo "Is entity dirty? " . ($product->isDirty() ? "Yes" : "No") . "\n";
    echo "Dirty attributes: " . implode(', ', $product->getDirtyAttributes()) . "\n";
    echo "Dirty data:\n";
    foreach ($product->getDirtyData() as $key => $value) {
        echo "  {$key}: {$value}\n";
    }
    echo "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Example 7: Type Casting
echo "Example 7: Automatic Type Casting\n";
echo str_repeat("-", 50) . "\n";

try {
    $configPath = __DIR__ . '/../app/Eav/Config/entities';
    $configLoader = new ConfigLoader($configPath);
    $productType = $configLoader->load('product');
    
    $product = new Entity($productType);
    
    // String values are automatically casted
    $product->setDataValue('price', '99.99'); // String -> Decimal
    $product->setDataValue('quantity', '100'); // String -> Int
    
    $priceAttr = $productType->getAttribute('price');
    $quantityAttr = $productType->getAttribute('quantity');
    
    echo "Price value: " . var_export($product->getDataValue('price'), true) . "\n";
    echo "Price type: " . gettype($product->getDataValue('price')) . "\n";
    echo "Quantity value: " . var_export($product->getDataValue('quantity'), true) . "\n";
    echo "Quantity type: " . gettype($product->getDataValue('quantity')) . "\n";
    echo "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

// Example 8: Attribute Collection Filtering
echo "Example 8: Advanced Attribute Collection Filtering\n";
echo str_repeat("-", 50) . "\n";

try {
    $configPath = __DIR__ . '/../app/Eav/Config/entities';
    $configLoader = new ConfigLoader($configPath);
    $productType = $configLoader->load('product');
    $attributes = $productType->getAttributes();
    
    echo "Attributes by Backend Type:\n";
    foreach (['varchar', 'int', 'decimal', 'datetime', 'text'] as $type) {
        $attrs = $attributes->getByBackendType($type);
        echo "  {$type}: " . count($attrs) . " attributes\n";
    }
    echo "\n";
    
    echo "Unique Attributes:\n";
    foreach ($attributes->getUnique() as $attr) {
        echo "  - {$attr->getAttributeLabel()}\n";
    }
    echo "\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n\n";
}

echo "=== Examples Complete ===\n";
