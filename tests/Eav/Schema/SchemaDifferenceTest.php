<?php

namespace Tests\Eav\Schema;

use PHPUnit\Framework\TestCase;
use App\Eav\Schema\SchemaDifference;

/**
 * Unit Tests for SchemaDifference
 */
class SchemaDifferenceTest extends TestCase
{
    public function testCreateDifference(): void
    {
        $diff = new SchemaDifference(
            'customer',
            SchemaDifference::TYPE_MISSING_TABLE,
            SchemaDifference::SEVERITY_CRITICAL,
            SchemaDifference::ACTION_ADD,
            'Test table is missing',
            ['table_name' => 'test_table'],
            'test_table'
        );

        $this->assertEquals('customer', $diff->getEntityTypeCode());
        $this->assertEquals(SchemaDifference::TYPE_MISSING_TABLE, $diff->getType());
        $this->assertEquals(SchemaDifference::SEVERITY_CRITICAL, $diff->getSeverity());
        $this->assertEquals(SchemaDifference::ACTION_ADD, $diff->getAction());
        $this->assertEquals('Test table is missing', $diff->getDescription());
        $this->assertEquals('test_table', $diff->getTableName());
    }

    public function testRiskScoreCalculation(): void
    {
        // Critical severity
        $criticalDiff = new SchemaDifference(
            'customer',
            SchemaDifference::TYPE_MISSING_TABLE,
            SchemaDifference::SEVERITY_CRITICAL,
            SchemaDifference::ACTION_ADD,
            'Critical issue'
        );
        $this->assertGreaterThan(30, $criticalDiff->getRiskScore());

        // Low severity
        $lowDiff = new SchemaDifference(
            'customer',
            SchemaDifference::TYPE_MISSING_INDEX,
            SchemaDifference::SEVERITY_LOW,
            SchemaDifference::ACTION_ADD,
            'Low priority issue'
        );
        $this->assertLessThan(20, $lowDiff->getRiskScore());
    }

    public function testDestructiveOperations(): void
    {
        $dropDiff = new SchemaDifference(
            'customer',
            SchemaDifference::TYPE_ORPHANED_TABLE,
            SchemaDifference::SEVERITY_HIGH,
            SchemaDifference::ACTION_DROP,
            'Drop orphaned table'
        );

        $this->assertTrue($dropDiff->isDestructive());

        $addDiff = new SchemaDifference(
            'customer',
            SchemaDifference::TYPE_MISSING_TABLE,
            SchemaDifference::SEVERITY_CRITICAL,
            SchemaDifference::ACTION_ADD,
            'Add table'
        );

        $this->assertFalse($addDiff->isDestructive());
    }

    public function testToArray(): void
    {
        $diff = new SchemaDifference(
            'customer',
            SchemaDifference::TYPE_MISSING_COLUMN,
            SchemaDifference::SEVERITY_HIGH,
            SchemaDifference::ACTION_ADD,
            'Column missing',
            ['column_name' => 'test_col'],
            'test_table',
            'test_col'
        );

        $array = $diff->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('entity_type_code', $array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('severity', $array);
        $this->assertArrayHasKey('action', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('risk_score', $array);
        $this->assertArrayHasKey('is_destructive', $array);
    }
}
