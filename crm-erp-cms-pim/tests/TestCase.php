<?php

namespace Tests;

/**
 * Base Test Case
 * 
 * Provides common testing utilities
 */
abstract class TestCase
{
    protected static $db;
    
    /**
     * Setup before each test
     */
    public function setUp(): void
    {
        // Initialize test database connection
        $this->initDatabase();
        
        // Begin transaction
        $this->beginTransaction();
    }
    
    /**
     * Teardown after each test
     */
    protected function tearDown(): void
    {
        // Rollback transaction
        $this->rollbackTransaction();
    }
    
    /**
     * Initialize test database
     */
    protected function initDatabase(): void
    {
        if (!self::$db) {
            require_once __DIR__ . '/../config/database.php';
            self::$db = \Core\Database\DB::getInstance();
        }
    }
    
    /**
     * Begin database transaction
     */
    protected function beginTransaction(): void
    {
        self::$db->getConnection()->beginTransaction();
    }
    
    /**
     * Rollback database transaction
     */
    protected function rollbackTransaction(): void
    {
        if (self::$db->getConnection()->inTransaction()) {
            self::$db->getConnection()->rollback();
        }
    }
    
    /**
     * Assert equals
     */
    protected function assertEquals($expected, $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new \Exception($message ?: "Expected {$expected}, got {$actual}");
        }
    }
    
    /**
     * Assert true
     */
    protected function assertTrue($condition, string $message = ''): void
    {
        if (!$condition) {
            throw new \Exception($message ?: "Assertion failed");
        }
    }
    
    /**
     * Assert false
     */
    protected function assertFalse($condition, string $message = ''): void
    {
        if ($condition) {
            throw new \Exception($message ?: "Assertion failed");
        }
    }
    
    /**
     * Assert null
     */
    protected function assertNull($value, string $message = ''): void
    {
        if ($value !== null) {
            throw new \Exception($message ?: "Expected null");
        }
    }
    
    /**
     * Assert not null
     */
    protected function assertNotNull($value, string $message = ''): void
    {
        if ($value === null) {
            throw new \Exception($message ?: "Expected not null");
        }
    }
    
    /**
     * Assert array has key
     */
    protected function assertArrayHasKey($key, array $array, string $message = ''): void
    {
        if (!array_key_exists($key, $array)) {
            throw new \Exception($message ?: "Array does not have key: {$key}");
        }
    }
    
    /**
     * Assert count
     */
    protected function assertCount(int $expected, $haystack, string $message = ''): void
    {
        $count = is_array($haystack) ? count($haystack) : count((array)$haystack);
        if ($count !== $expected) {
            throw new \Exception($message ?: "Expected count {$expected}, got {$count}");
        }
    }
    
    /**
     * Create mock user
     */
    protected function createMockUser(array $data = []): object
    {
        return (object) array_merge([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
        ], $data);
    }
}
