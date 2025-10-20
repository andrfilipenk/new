<?php
// app/Eav/Tests/JoinOptimizerTest.php
namespace Eav\Tests;

use PHPUnit\Framework\TestCase;
use Eav\Query\JoinOptimizer;
use Eav\Models\Attribute;

/**
 * Join Optimizer Tests
 */
class JoinOptimizerTest extends TestCase
{
    private function createMockAttribute($id, $code, $backendType)
    {
        $attr = $this->createMock(Attribute::class);
        $attr->id = $id;
        $attr->attribute_code = $code;
        $attr->backend_type = $backendType;
        return $attr;
    }

    public function testBuildJoin()
    {
        $optimizer = new JoinOptimizer();
        $attribute = $this->createMockAttribute(1, 'name', 'varchar');

        $join = $optimizer->buildJoin($attribute);

        $this->assertEquals('LEFT', $join['type']);
        $this->assertEquals('eav_values_varchar', $join['table']);
        $this->assertEquals('val_1', $join['alias']);
        $this->assertStringContainsString('e.id = val_1.entity_id', $join['on']);
        $this->assertEquals('varchar', $join['backend_type']);
    }

    public function testGetTableAlias()
    {
        $optimizer = new JoinOptimizer();
        
        $this->assertEquals('val_1', $optimizer->getTableAlias(1));
        $this->assertEquals('val_100', $optimizer->getTableAlias(100));
    }

    public function testOptimizeJoinsWithFewAttributes()
    {
        $optimizer = new JoinOptimizer();
        
        $attributes = [
            $this->createMockAttribute(1, 'name', 'varchar'),
            $this->createMockAttribute(2, 'price', 'decimal'),
            $this->createMockAttribute(3, 'description', 'text')
        ];

        $result = $optimizer->optimizeJoins($attributes);

        $this->assertArrayHasKey('joins', $result);
        $this->assertArrayHasKey('use_subquery', $result);
        $this->assertFalse($result['use_subquery']); // Should not use subquery for few attributes
        $this->assertCount(3, $result['joins']);
    }

    public function testShouldUseSubqueryForManyAttributes()
    {
        $optimizer = new JoinOptimizer();
        $optimizer->setMaxJoins(5);

        $this->assertFalse($optimizer->shouldUseSubquery(3));
        $this->assertFalse($optimizer->shouldUseSubquery(5));
        $this->assertTrue($optimizer->shouldUseSubquery(6));
        $this->assertTrue($optimizer->shouldUseSubquery(20));
    }

    public function testBuildSubquery()
    {
        $optimizer = new JoinOptimizer();
        $attribute = $this->createMockAttribute(1, 'name', 'varchar');

        $subquery = $optimizer->buildSubquery($attribute);

        $this->assertStringContainsString('SELECT value FROM eav_values_varchar', $subquery);
        $this->assertStringContainsString('entity_id = e.id', $subquery);
        $this->assertStringContainsString('attribute_id = 1', $subquery);
        $this->assertStringContainsString('AS name', $subquery);
    }

    public function testOptimizeFilterJoins()
    {
        $optimizer = new JoinOptimizer();
        
        $attributes = [
            $this->createMockAttribute(1, 'name', 'varchar'),
            $this->createMockAttribute(2, 'price', 'decimal'),
            $this->createMockAttribute(3, 'category', 'varchar')
        ];

        $filters = [
            ['attribute' => 'name', 'operator' => '=', 'value' => 'test'],
            ['attribute' => 'price', 'operator' => '>', 'value' => 50]
        ];

        $joins = $optimizer->optimizeFilterJoins($filters, $attributes);

        // Should only create joins for filtered attributes
        $this->assertCount(2, $joins);
        $this->assertEquals('val_1', $joins[0]['alias']);
        $this->assertEquals('val_2', $joins[1]['alias']);
    }

    public function testEstimateJoinCount()
    {
        $optimizer = new JoinOptimizer();
        $optimizer->setMaxJoins(10);

        $attributes = array_map(
            fn($i) => $this->createMockAttribute($i, "attr{$i}", 'varchar'),
            range(1, 15)
        );

        $filters = [];
        $count = $optimizer->estimateJoinCount($attributes, $filters);

        $this->assertEquals(10, $count); // Should be limited to max joins
    }

    public function testSetMaxJoins()
    {
        $optimizer = new JoinOptimizer();
        
        $optimizer->setMaxJoins(5);
        $this->assertTrue($optimizer->shouldUseSubquery(6));
        
        $optimizer->setMaxJoins(20);
        $this->assertFalse($optimizer->shouldUseSubquery(6));
    }

    public function testBuildBatchJoin()
    {
        $optimizer = new JoinOptimizer();
        
        $attributes = [
            $this->createMockAttribute(1, 'name', 'varchar'),
            $this->createMockAttribute(2, 'sku', 'varchar'),
            $this->createMockAttribute(3, 'category', 'varchar')
        ];

        $join = $optimizer->buildBatchJoin($attributes);

        $this->assertEquals('LEFT', $join['type']);
        $this->assertEquals('eav_values_varchar', $join['table']);
        $this->assertStringContainsString('val_batch_varchar', $join['alias']);
        $this->assertStringContainsString('IN (1,2,3)', $join['on']);
        $this->assertTrue($join['is_batch']);
    }
}
