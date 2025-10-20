<?php
// app/Eav/Tests/IntegrationTest.php
namespace Eav\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Integration Tests for EAV System
 * 
 * These tests verify end-to-end functionality across all EAV components.
 * Note: These tests require a database connection and should be run in a test environment.
 */
class IntegrationTest extends TestCase
{
    protected $di;
    protected $entityTypeId;
    protected $attributeIds = [];

    /**
     * Setup test environment
     * 
     * This would typically:
     * 1. Create test database connection
     * 2. Run migrations
     * 3. Setup DI container
     * 4. Create test entity type and attributes
     */
    protected function setUp(): void
    {
        // Note: Actual implementation would require database setup
        $this->markTestSkipped('Integration tests require database setup');
    }

    /**
     * Test complete CRUD workflow
     */
    public function testCompleteCRUDWorkflow()
    {
        $entityManager = $this->di->get('eavEntityManager');
        
        // CREATE
        $entity = $entityManager->create($this->entityTypeId, [
            'name' => 'Test Product',
            'price' => 99.99,
            'stock_quantity' => 100,
            'category' => 'Electronics'
        ]);
        
        $this->assertNotNull($entity);
        $this->assertNotNull($entity->id);
        
        // READ
        $loaded = $entityManager->find($entity->id, true);
        $this->assertNotNull($loaded);
        $this->assertEquals('Test Product', $loaded->attributeValues['name']);
        $this->assertEquals(99.99, $loaded->attributeValues['price']);
        
        // UPDATE
        $updated = $entityManager->update($entity->id, [
            'price' => 89.99,
            'stock_quantity' => 95
        ]);
        
        $this->assertTrue($updated);
        
        $reloaded = $entityManager->find($entity->id, true);
        $this->assertEquals(89.99, $reloaded->attributeValues['price']);
        $this->assertEquals(95, $reloaded->attributeValues['stock_quantity']);
        
        // DELETE (soft)
        $deleted = $entityManager->delete($entity->id, true);
        $this->assertTrue($deleted);
        
        $afterDelete = $entityManager->find($entity->id);
        $this->assertNull($afterDelete); // Should not find soft-deleted entity
    }

    /**
     * Test complex query scenarios
     */
    public function testComplexQueries()
    {
        $repository = $this->di->get('eavEntityRepository');
        
        // Create test data
        $testData = [
            ['name' => 'Product A', 'price' => 10.00, 'category' => 'Electronics'],
            ['name' => 'Product B', 'price' => 25.00, 'category' => 'Electronics'],
            ['name' => 'Product C', 'price' => 50.00, 'category' => 'Tools'],
            ['name' => 'Product D', 'price' => 75.00, 'category' => 'Tools'],
            ['name' => 'Product E', 'price' => 100.00, 'category' => 'Gadgets'],
        ];
        
        foreach ($testData as $data) {
            $this->di->get('eavEntityManager')->create($this->entityTypeId, $data);
        }
        
        // Test simple filter
        $electronics = $repository->findByAttribute(
            $this->entityTypeId,
            'category',
            'Electronics'
        );
        $this->assertCount(2, $electronics);
        
        // Test range query
        $midRange = $repository->whereBetween(
            $this->entityTypeId,
            'price',
            20,
            60
        );
        $this->assertCount(2, $midRange);
        
        // Test complex query
        $results = $repository->query($this->entityTypeId)
            ->where('price', '>', 20)
            ->where('price', '<', 80)
            ->orderBy('price', 'ASC')
            ->get();
        
        $this->assertCount(3, $results);
        $this->assertEquals(25.00, $results[0]->attributeValues['price']);
        $this->assertEquals(75.00, $results[2]->attributeValues['price']);
        
        // Test IN clause
        $specific = $repository->whereIn(
            $this->entityTypeId,
            'category',
            ['Electronics', 'Gadgets']
        );
        $this->assertCount(3, $specific);
        
        // Test pagination
        $page1 = $repository->paginate($this->entityTypeId, 1, 2);
        $this->assertCount(2, $page1['data']);
        $this->assertEquals(5, $page1['total']);
        $this->assertEquals(3, $page1['total_pages']);
    }

    /**
     * Test batch operations
     */
    public function testBatchOperations()
    {
        $batchManager = $this->di->get('eavBatchManager');
        
        // Batch create
        $batchData = [];
        for ($i = 1; $i <= 50; $i++) {
            $batchData[] = [
                'name' => "Batch Product {$i}",
                'price' => $i * 10,
                'stock_quantity' => $i * 5
            ];
        }
        
        $entityIds = $batchManager->batchCreate($this->entityTypeId, $batchData);
        $this->assertCount(50, $entityIds);
        
        // Batch update
        $attributeRepository = $this->di->get('eavAttributeRepository');
        $priceAttr = $attributeRepository->findByCode('price', $this->entityTypeId);
        
        $updates = [];
        foreach (array_slice($entityIds, 0, 10) as $id) {
            $updates[] = [
                'entity_id' => $id,
                'attribute' => $priceAttr,
                'value' => 99.99
            ];
        }
        
        $result = $batchManager->batchUpdateValues($updates);
        $this->assertTrue($result);
        
        // Batch delete
        $toDelete = array_slice($entityIds, 0, 20);
        $deletedCount = $batchManager->batchDelete($toDelete, true);
        $this->assertEquals(20, $deletedCount);
    }

    /**
     * Test caching functionality
     */
    public function testCaching()
    {
        $entityManager = $this->di->get('eavEntityManager');
        $cacheManager = $this->di->get('eavCacheManager');
        
        // Create entity
        $entity = $entityManager->create($this->entityTypeId, [
            'name' => 'Cached Product',
            'price' => 50.00
        ]);
        
        // First load - should cache
        $loaded1 = $entityManager->find($entity->id, true);
        
        // Second load - should come from cache
        $loaded2 = $entityManager->find($entity->id, true);
        
        $this->assertEquals($loaded1->attributeValues, $loaded2->attributeValues);
        
        // Update should invalidate cache
        $entityManager->update($entity->id, ['price' => 60.00]);
        
        // Next load should fetch fresh data
        $loaded3 = $entityManager->find($entity->id, true);
        $this->assertEquals(60.00, $loaded3->attributeValues['price']);
        
        // Test cache statistics
        $stats = $cacheManager->getStats();
        $this->assertArrayHasKey('total_entries', $stats);
        $this->assertArrayHasKey('active_entries', $stats);
    }

    /**
     * Test value validation
     */
    public function testValueValidation()
    {
        $entityManager = $this->di->get('eavEntityManager');
        
        // Test required field validation
        try {
            $entityManager->create($this->entityTypeId, [
                'price' => 10.00
                // 'name' is required but missing
            ]);
            $this->fail('Should throw exception for missing required field');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Invalid value', $e->getMessage());
        }
        
        // Test type validation
        try {
            $entityManager->create($this->entityTypeId, [
                'name' => 'Valid Name',
                'price' => 'not a number' // Should be decimal
            ]);
            $this->fail('Should throw exception for invalid type');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Invalid value', $e->getMessage());
        }
    }

    /**
     * Test repository patterns
     */
    public function testRepositoryPatterns()
    {
        $repository = $this->di->get('eavEntityRepository');
        
        // Test firstOrCreate
        $entity1 = $repository->firstOrCreate(
            $this->entityTypeId,
            ['name' => 'Unique Product'],
            ['price' => 100.00]
        );
        $this->assertNotNull($entity1->id);
        
        // Should return existing entity
        $entity2 = $repository->firstOrCreate(
            $this->entityTypeId,
            ['name' => 'Unique Product'],
            ['price' => 200.00]
        );
        $this->assertEquals($entity1->id, $entity2->id);
        $this->assertEquals(100.00, $entity2->attributeValues['price']); // Original price
        
        // Test updateOrCreate
        $entity3 = $repository->updateOrCreate(
            $this->entityTypeId,
            ['name' => 'Update or Create Product'],
            ['price' => 50.00]
        );
        $this->assertEquals(50.00, $entity3->attributeValues['price']);
        
        // Should update existing
        $entity4 = $repository->updateOrCreate(
            $this->entityTypeId,
            ['name' => 'Update or Create Product'],
            ['price' => 75.00]
        );
        $this->assertEquals($entity3->id, $entity4->id);
        $this->assertEquals(75.00, $entity4->attributeValues['price']);
    }

    /**
     * Test index management
     */
    public function testIndexManagement()
    {
        $indexManager = $this->di->get('eavIndexManager');
        $attributeRepository = $this->di->get('eavAttributeRepository');
        
        // Get searchable attributes
        $searchableAttrs = $attributeRepository->getSearchableAttributes($this->entityTypeId);
        
        // Create indexes
        foreach ($searchableAttrs as $attr) {
            $result = $indexManager->createAttributeIndex($attr->id, $attr->backend_type);
            $this->assertTrue($result);
        }
        
        // Rebuild all indexes
        $result = $indexManager->rebuildIndexes($this->entityTypeId);
        $this->assertTrue($result);
        
        // Get statistics
        $stats = $indexManager->getTableStats();
        $this->assertArrayHasKey('eav_entities', $stats);
        $this->assertArrayHasKey('eav_values_varchar', $stats);
    }

    /**
     * Test entity copying
     */
    public function testEntityCopying()
    {
        $entityManager = $this->di->get('eavEntityManager');
        
        // Create original entity
        $original = $entityManager->create($this->entityTypeId, [
            'name' => 'Original Product',
            'price' => 100.00,
            'stock_quantity' => 50
        ]);
        
        // Copy entity
        $copy = $entityManager->copy($original->id);
        
        $this->assertNotNull($copy);
        $this->assertNotEquals($original->id, $copy->id);
        
        // Load and verify
        $loadedCopy = $entityManager->find($copy->id, true);
        $this->assertEquals('Original Product', $loadedCopy->attributeValues['name']);
        $this->assertEquals(100.00, $loadedCopy->attributeValues['price']);
        
        // Copy with overrides
        $copyWithOverride = $entityManager->copy($original->id, [
            'name' => 'Modified Copy',
            'price' => 150.00
        ]);
        
        $loadedModified = $entityManager->find($copyWithOverride->id, true);
        $this->assertEquals('Modified Copy', $loadedModified->attributeValues['name']);
        $this->assertEquals(150.00, $loadedModified->attributeValues['price']);
        $this->assertEquals(50, $loadedModified->attributeValues['stock_quantity']); // Original value
    }

    /**
     * Cleanup test environment
     */
    protected function tearDown(): void
    {
        // Note: Actual implementation would clean up test data
    }
}
