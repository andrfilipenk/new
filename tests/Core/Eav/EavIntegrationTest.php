<?php
// tests/Core/Eav/EavIntegrationTest.php
// Integration test for EAV Library Phase 2

require_once __DIR__ . '/../../../bootstrap.php';

use Core\Di\Container;

class EavIntegrationTest
{
    private $di;
    private $schemaManager;
    private $entityManager;
    private $registry;
    private $repository;
    private $db;
    
    private $testResults = [];

    public function __construct()
    {
        $this->di = Container::getDefault();
        $this->schemaManager = $this->di->get('eav.schema_manager');
        $this->entityManager = $this->di->get('eav.entity_manager');
        $this->registry = $this->di->get('eav.registry');
        $this->repository = $this->di->get('eav.entity_repository');
        $this->db = $this->di->get('db');
    }

    public function runAll()
    {
        echo "=== EAV Library Phase 2 Integration Tests ===\n\n";

        $this->testSchemaInitialization();
        $this->testEntityTypeSync();
        $this->testEntityCreation();
        $this->testEntityLoading();
        $this->testEntityUpdate();
        $this->testMultipleEntities();
        $this->testRepositoryQueries();
        $this->testValidation();
        $this->testDirtyTracking();
        $this->testEntityDeletion();

        $this->printResults();
    }

    private function testSchemaInitialization()
    {
        $this->test("Schema Initialization", function() {
            $this->schemaManager->initialize();
            
            // Verify tables exist
            $tables = [
                'eav_entity_type',
                'eav_attribute',
                'eav_value_varchar',
                'eav_value_int',
                'eav_value_decimal',
                'eav_value_datetime',
                'eav_value_text'
            ];

            foreach ($tables as $table) {
                $result = $this->db->execute("SHOW TABLES LIKE ?", [$table])->fetch();
                $this->assertTrue(!empty($result), "Table {$table} should exist");
            }

            return true;
        });
    }

    private function testEntityTypeSync()
    {
        $this->test("Entity Type Synchronization", function() {
            $productType = $this->registry->get('product');
            $this->schemaManager->synchronize($productType);

            // Verify entity type record
            $record = $this->db->table('eav_entity_type')
                ->where('entity_code', 'product')
                ->first();

            $this->assertTrue(!empty($record), "Entity type record should exist");
            $this->assertEqual($record['entity_label'], 'Product');

            // Verify entity table
            $tableResult = $this->db->execute(
                "SHOW TABLES LIKE ?",
                [$productType->getEntityTable()]
            )->fetch();
            $this->assertTrue(!empty($tableResult), "Entity table should exist");

            // Verify attributes
            $attributes = $this->db->table('eav_attribute')
                ->where('entity_type_id', $record['entity_type_id'])
                ->get();

            $this->assertTrue(count($attributes) > 0, "Attributes should be synced");

            return true;
        });
    }

    private function testEntityCreation()
    {
        $this->test("Entity Creation", function() {
            $productType = $this->registry->get('product');

            $data = [
                'name' => 'Test Product',
                'sku' => 'TEST-001',
                'description' => 'Test description',
                'price' => 99.99,
                'quantity' => 10,
                'is_active' => 1
            ];

            $entity = $this->entityManager->create($productType, $data);

            $this->assertTrue($entity->getId() > 0, "Entity should have an ID");
            $this->assertEqual($entity->get('name'), 'Test Product');
            $this->assertEqual($entity->get('sku'), 'TEST-001');
            $this->assertEqual($entity->get('price'), 99.99);

            // Verify in database
            $record = $this->db->table($productType->getEntityTable())
                ->where('entity_id', $entity->getId())
                ->first();

            $this->assertTrue(!empty($record), "Entity record should exist in database");

            return $entity;
        });
    }

    private function testEntityLoading()
    {
        $this->test("Entity Loading", function() {
            $productType = $this->registry->get('product');

            // Create entity first
            $data = [
                'name' => 'Load Test Product',
                'sku' => 'LOAD-001',
                'price' => 49.99,
                'quantity' => 5,
                'is_active' => 1
            ];

            $created = $this->entityManager->create($productType, $data);

            // Load it back
            $loaded = $this->entityManager->load($productType, $created->getId());

            $this->assertTrue($loaded !== null, "Entity should be loaded");
            $this->assertEqual($loaded->getId(), $created->getId());
            $this->assertEqual($loaded->get('name'), 'Load Test Product');
            $this->assertEqual($loaded->get('sku'), 'LOAD-001');
            $this->assertFalse($loaded->isDirty(), "Loaded entity should not be dirty");

            return true;
        });
    }

    private function testEntityUpdate()
    {
        $this->test("Entity Update", function() {
            $productType = $this->registry->get('product');

            // Create entity
            $entity = $this->entityManager->create($productType, [
                'name' => 'Update Test',
                'sku' => 'UPDATE-001',
                'price' => 100.00,
                'quantity' => 20,
                'is_active' => 1
            ]);

            // Update it
            $entity->set('price', 89.99);
            $entity->set('quantity', 15);

            $this->assertTrue($entity->isDirty(), "Entity should be dirty after changes");
            $this->assertTrue(in_array('price', $entity->getDirtyAttributes()));
            $this->assertTrue(in_array('quantity', $entity->getDirtyAttributes()));

            $this->entityManager->save($entity);

            $this->assertFalse($entity->isDirty(), "Entity should not be dirty after save");

            // Reload and verify
            $loaded = $this->entityManager->load($productType, $entity->getId());
            $this->assertEqual($loaded->get('price'), 89.99);
            $this->assertEqual($loaded->get('quantity'), 15);

            return true;
        });
    }

    private function testMultipleEntities()
    {
        $this->test("Multiple Entity Loading", function() {
            $productType = $this->registry->get('product');

            // Create multiple entities
            $ids = [];
            for ($i = 1; $i <= 3; $i++) {
                $entity = $this->entityManager->create($productType, [
                    'name' => "Multi Product {$i}",
                    'sku' => "MULTI-00{$i}",
                    'price' => 10.00 * $i,
                    'quantity' => $i * 5,
                    'is_active' => 1
                ]);
                $ids[] = $entity->getId();
            }

            // Load them all
            $entities = $this->entityManager->loadMultiple($productType, $ids);

            $this->assertEqual(count($entities), 3, "Should load 3 entities");

            foreach ($entities as $id => $entity) {
                $this->assertTrue(in_array($id, $ids));
                $this->assertEqual($entity->getId(), $id);
            }

            return true;
        });
    }

    private function testRepositoryQueries()
    {
        $this->test("Repository Queries", function() {
            $productType = $this->registry->get('product');

            // Create test data
            $this->entityManager->create($productType, [
                'name' => 'Query Test Product',
                'sku' => 'QUERY-001',
                'price' => 25.00,
                'quantity' => 100,
                'is_active' => 1
            ]);

            // Test findByAttribute
            $found = $this->repository->findByAttribute($productType, 'sku', 'QUERY-001');
            $this->assertTrue(count($found) > 0, "Should find product by SKU");

            // Test search
            $searched = $this->repository->search($productType, 'Query');
            $this->assertTrue(count($searched) > 0, "Should find products by search");

            // Test findAll
            $all = $this->repository->findAll($productType, ['limit' => 5]);
            $this->assertTrue(count($all) > 0, "Should find all products");

            // Test pagination
            $paginated = $this->repository->paginate($productType, [], 2, 1);
            $this->assertTrue(isset($paginated['data']), "Pagination should have data");
            $this->assertTrue($paginated['total'] > 0, "Pagination should have total");

            return true;
        });
    }

    private function testValidation()
    {
        $this->test("Entity Validation", function() {
            $productType = $this->registry->get('product');

            try {
                // Try to create without required fields
                $this->entityManager->create($productType, [
                    'description' => 'Missing required fields'
                ]);

                $this->fail("Should throw validation exception");
            } catch (\Core\Exception\ValidationException $e) {
                $this->assertTrue(true, "Validation exception thrown as expected");
                $errors = $e->getErrors();
                $this->assertTrue(isset($errors['name']), "Should have name error");
            }

            return true;
        });
    }

    private function testDirtyTracking()
    {
        $this->test("Dirty Tracking", function() {
            $productType = $this->registry->get('product');

            $entity = $this->entityManager->create($productType, [
                'name' => 'Dirty Test',
                'sku' => 'DIRTY-001',
                'price' => 50.00,
                'quantity' => 10,
                'is_active' => 1
            ]);

            $this->assertFalse($entity->isDirty(), "New entity should not be dirty after creation");

            $entity->set('price', 45.00);
            $this->assertTrue($entity->isDirty(), "Entity should be dirty after modification");

            $dirtyAttrs = $entity->getDirtyAttributes();
            $this->assertEqual(count($dirtyAttrs), 1);
            $this->assertEqual($dirtyAttrs[0], 'price');

            $entity->set('quantity', 15);
            $this->assertEqual(count($entity->getDirtyAttributes()), 2);

            $this->entityManager->save($entity);
            $this->assertFalse($entity->isDirty(), "Entity should not be dirty after save");

            return true;
        });
    }

    private function testEntityDeletion()
    {
        $this->test("Entity Deletion", function() {
            $productType = $this->registry->get('product');

            $entity = $this->entityManager->create($productType, [
                'name' => 'Delete Test',
                'sku' => 'DELETE-001',
                'price' => 30.00,
                'quantity' => 5,
                'is_active' => 1
            ]);

            $id = $entity->getId();

            // Delete it
            $this->entityManager->delete($entity);

            // Try to load
            $loaded = $this->entityManager->load($productType, $id);
            $this->assertTrue($loaded === null, "Deleted entity should not be loadable");

            // Verify values are deleted
            $values = $this->db->table('eav_value_varchar')
                ->where('entity_id', $id)
                ->get();

            $this->assertEqual(count($values), 0, "Values should be deleted");

            return true;
        });
    }

    // Test helper methods
    private function test($name, $callback)
    {
        echo "Testing: {$name}... ";
        try {
            $result = $callback();
            echo "✓ PASS\n";
            $this->testResults[] = ['name' => $name, 'status' => 'PASS'];
        } catch (\Exception $e) {
            echo "✗ FAIL\n";
            echo "  Error: " . $e->getMessage() . "\n";
            $this->testResults[] = [
                'name' => $name,
                'status' => 'FAIL',
                'error' => $e->getMessage()
            ];
        }
    }

    private function assertTrue($condition, $message = "Assertion failed")
    {
        if (!$condition) {
            throw new \Exception($message);
        }
    }

    private function assertFalse($condition, $message = "Assertion failed")
    {
        if ($condition) {
            throw new \Exception($message);
        }
    }

    private function assertEqual($actual, $expected, $message = null)
    {
        if ($actual !== $expected) {
            $msg = $message ?? "Expected '{$expected}', got '{$actual}'";
            throw new \Exception($msg);
        }
    }

    private function fail($message)
    {
        throw new \Exception($message);
    }

    private function printResults()
    {
        echo "\n=== Test Results ===\n";
        $passed = 0;
        $failed = 0;

        foreach ($this->testResults as $result) {
            if ($result['status'] === 'PASS') {
                $passed++;
            } else {
                $failed++;
            }
        }

        echo "Total: " . count($this->testResults) . "\n";
        echo "Passed: {$passed}\n";
        echo "Failed: {$failed}\n";

        if ($failed > 0) {
            echo "\nFailed tests:\n";
            foreach ($this->testResults as $result) {
                if ($result['status'] === 'FAIL') {
                    echo "  - {$result['name']}: {$result['error']}\n";
                }
            }
        }
    }
}

// Run tests
$test = new EavIntegrationTest();
$test->runAll();
