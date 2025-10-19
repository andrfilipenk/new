<?php
// app/Eav/Tests/CacheManagerTest.php
namespace Eav\Tests;

use PHPUnit\Framework\TestCase;
use Eav\Cache\CacheManager;

/**
 * Cache Manager Tests
 */
class CacheManagerTest extends TestCase
{
    public function testSetAndGet()
    {
        $db = $this->createMock(\Core\Database\Database::class);
        
        // Mock table method to return a mock query builder
        $queryBuilder = $this->createMock(\Core\Database\Database::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('first')->willReturn(null);
        $queryBuilder->method('insert')->willReturn(1);
        
        $db->method('table')->willReturn($queryBuilder);
        
        $cache = new CacheManager($db);
        
        // Test memory cache
        $cache->set('test_key', 'test_value', 3600);
        $value = $cache->get('test_key');
        
        $this->assertEquals('test_value', $value);
    }

    public function testHas()
    {
        $db = $this->createMock(\Core\Database\Database::class);
        $cache = new CacheManager($db);
        
        $cache->set('existing_key', 'value');
        
        $this->assertTrue($cache->has('existing_key'));
        $this->assertFalse($cache->has('non_existing_key'));
    }

    public function testDelete()
    {
        $db = $this->createMock(\Core\Database\Database::class);
        $queryBuilder = $this->createMock(\Core\Database\Database::class);
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('delete')->willReturn(1);
        
        $db->method('table')->willReturn($queryBuilder);
        
        $cache = new CacheManager($db);
        
        $cache->set('test_key', 'test_value');
        $this->assertTrue($cache->has('test_key'));
        
        $cache->delete('test_key');
        $this->assertFalse($cache->has('test_key'));
    }

    public function testRememberCallbackOnCacheMiss()
    {
        $db = $this->createMock(\Core\Database\Database::class);
        $cache = new CacheManager($db);
        
        $callCount = 0;
        $callback = function() use (&$callCount) {
            $callCount++;
            return 'computed_value';
        };
        
        $value = $cache->remember('new_key', $callback, 3600);
        
        $this->assertEquals('computed_value', $value);
        $this->assertEquals(1, $callCount);
    }

    public function testRememberDoesNotCallCallbackOnCacheHit()
    {
        $db = $this->createMock(\Core\Database\Database::class);
        $cache = new CacheManager($db);
        
        $cache->set('existing_key', 'cached_value');
        
        $callCount = 0;
        $callback = function() use (&$callCount) {
            $callCount++;
            return 'computed_value';
        };
        
        $value = $cache->remember('existing_key', $callback, 3600);
        
        $this->assertEquals('cached_value', $value);
        $this->assertEquals(0, $callCount); // Callback should not be called
    }

    public function testInvalidateEntity()
    {
        $db = $this->createMock(\Core\Database\Database::class);
        $queryBuilder = $this->createMock(\Core\Database\Database::class);
        $queryBuilder->method('whereRaw')->willReturnSelf();
        $queryBuilder->method('where')->willReturnSelf();
        $queryBuilder->method('delete')->willReturn(1);
        
        $db->method('table')->willReturn($queryBuilder);
        
        $cache = new CacheManager($db);
        
        $cache->set('entity:123:data', ['id' => 123]);
        $cache->set('entity:123:values', ['name' => 'test']);
        
        $cache->invalidateEntity(123);
        
        // After invalidation, should not be in memory cache
        $memoryCache = $cache->getMemoryCache();
        $this->assertArrayNotHasKey('eav:entity:123:data', $memoryCache);
    }

    public function testClearMemoryCache()
    {
        $db = $this->createMock(\Core\Database\Database::class);
        $cache = new CacheManager($db);
        
        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');
        
        $this->assertNotEmpty($cache->getMemoryCache());
        
        $cache->clearMemoryCache();
        
        $this->assertEmpty($cache->getMemoryCache());
    }

    public function testCachePrefix()
    {
        $db = $this->createMock(\Core\Database\Database::class);
        $cache = new CacheManager($db, 'custom_prefix:');
        
        $cache->set('test', 'value');
        
        $memoryCache = $cache->getMemoryCache();
        $this->assertArrayHasKey('custom_prefix:test', $memoryCache);
    }
}
