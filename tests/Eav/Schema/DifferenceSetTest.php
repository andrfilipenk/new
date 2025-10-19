<?php

namespace Tests\Eav\Schema;

use PHPUnit\Framework\TestCase;
use App\Eav\Schema\DifferenceSet;
use App\Eav\Schema\SchemaDifference;

/**
 * Unit Tests for DifferenceSet
 */
class DifferenceSetTest extends TestCase
{
    public function testAddDifference(): void
    {
        $set = new DifferenceSet('customer');

        $diff = new SchemaDifference(
            'customer',
            SchemaDifference::TYPE_MISSING_TABLE,
            SchemaDifference::SEVERITY_CRITICAL,
            SchemaDifference::ACTION_ADD,
            'Test difference'
        );

        $set->addDifference($diff);

        $this->assertEquals(1, $set->count());
        $this->assertTrue($set->hasDifferences());
    }

    public function testGetDifferencesByAction(): void
    {
        $set = new DifferenceSet('customer');

        $addDiff = new SchemaDifference(
            'customer',
            SchemaDifference::TYPE_MISSING_TABLE,
            SchemaDifference::SEVERITY_CRITICAL,
            SchemaDifference::ACTION_ADD,
            'Add table'
        );

        $modifyDiff = new SchemaDifference(
            'customer',
            SchemaDifference::TYPE_TYPE_MISMATCH,
            SchemaDifference::SEVERITY_HIGH,
            SchemaDifference::ACTION_MODIFY,
            'Modify column'
        );

        $set->addDifference($addDiff);
        $set->addDifference($modifyDiff);

        $addDiffs = $set->getDifferencesByAction(SchemaDifference::ACTION_ADD);
        $this->assertCount(1, $addDiffs);

        $modifyDiffs = $set->getDifferencesByAction(SchemaDifference::ACTION_MODIFY);
        $this->assertCount(1, $modifyDiffs);
    }

    public function testDestructiveDifferences(): void
    {
        $set = new DifferenceSet('customer');

        $safeDiff = new SchemaDifference(
            'customer',
            SchemaDifference::TYPE_MISSING_TABLE,
            SchemaDifference::SEVERITY_CRITICAL,
            SchemaDifference::ACTION_ADD,
            'Add table'
        );

        $destructiveDiff = new SchemaDifference(
            'customer',
            SchemaDifference::TYPE_ORPHANED_TABLE,
            SchemaDifference::SEVERITY_HIGH,
            SchemaDifference::ACTION_DROP,
            'Drop table'
        );

        $set->addDifference($safeDiff);
        $set->addDifference($destructiveDiff);

        $this->assertTrue($set->hasDestructiveDifferences());
        $this->assertCount(1, $set->getDestructiveDifferences());
    }

    public function testRiskScoreAccumulation(): void
    {
        $set = new DifferenceSet('customer');

        $diff1 = new SchemaDifference(
            'customer',
            SchemaDifference::TYPE_MISSING_TABLE,
            SchemaDifference::SEVERITY_CRITICAL,
            SchemaDifference::ACTION_ADD,
            'Add table'
        );

        $diff2 = new SchemaDifference(
            'customer',
            SchemaDifference::TYPE_MISSING_INDEX,
            SchemaDifference::SEVERITY_MEDIUM,
            SchemaDifference::ACTION_ADD,
            'Add index'
        );

        $set->addDifference($diff1);
        $set->addDifference($diff2);

        $riskScore = $set->getTotalRiskScore();
        $this->assertGreaterThan(0, $riskScore);
        $this->assertLessThanOrEqual(100, $riskScore);
    }

    public function testToArray(): void
    {
        $set = new DifferenceSet('customer');

        $diff = new SchemaDifference(
            'customer',
            SchemaDifference::TYPE_MISSING_TABLE,
            SchemaDifference::SEVERITY_CRITICAL,
            SchemaDifference::ACTION_ADD,
            'Test'
        );

        $set->addDifference($diff);

        $array = $set->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('entity_type_code', $array);
        $this->assertArrayHasKey('total_differences', $array);
        $this->assertArrayHasKey('total_risk_score', $array);
        $this->assertArrayHasKey('has_destructive', $array);
        $this->assertArrayHasKey('differences', $array);
    }
}
