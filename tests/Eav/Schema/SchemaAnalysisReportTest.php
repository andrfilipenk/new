<?php

namespace Tests\Eav\Schema;

use PHPUnit\Framework\TestCase;
use App\Eav\Schema\SchemaAnalysisReport;
use App\Eav\Schema\SchemaDifference;

/**
 * Unit Tests for SchemaAnalysisReport
 */
class SchemaAnalysisReportTest extends TestCase
{
    public function testCreateReport(): void
    {
        $report = new SchemaAnalysisReport('customer');

        $this->assertEquals('customer', $report->getEntityTypeCode());
        $this->assertFalse($report->hasDifferences());
        $this->assertEquals(0, $report->getRiskScore());
        $this->assertEquals('in_sync', $report->getStatus());
    }

    public function testAddDifference(): void
    {
        $report = new SchemaAnalysisReport('customer');

        $diff = new SchemaDifference(
            'customer',
            SchemaDifference::TYPE_MISSING_TABLE,
            SchemaDifference::SEVERITY_CRITICAL,
            SchemaDifference::ACTION_ADD,
            'Table missing'
        );

        $report->addDifference($diff);

        $this->assertTrue($report->hasDifferences());
        $this->assertCount(1, $report->getDifferences());
    }

    public function testRiskLevels(): void
    {
        $report = new SchemaAnalysisReport('customer');

        // Safe level (0-20)
        $report->setRiskScore(10);
        $this->assertEquals('safe', $report->getRiskLevel());

        // Low level (21-40)
        $report->setRiskScore(30);
        $this->assertEquals('low', $report->getRiskLevel());

        // Medium level (41-70)
        $report->setRiskScore(50);
        $this->assertEquals('medium', $report->getRiskLevel());

        // High level (71-90)
        $report->setRiskScore(80);
        $this->assertEquals('high', $report->getRiskLevel());

        // Dangerous level (91-100)
        $report->setRiskScore(95);
        $this->assertEquals('dangerous', $report->getRiskLevel());
    }

    public function testRecommendations(): void
    {
        $report = new SchemaAnalysisReport('customer');

        $report->addRecommendation('Run sync command');
        $report->addRecommendation('Create backup first');

        $recommendations = $report->getRecommendations();

        $this->assertCount(2, $recommendations);
        $this->assertContains('Run sync command', $recommendations);
        $this->assertContains('Create backup first', $recommendations);
    }

    public function testToArray(): void
    {
        $report = new SchemaAnalysisReport('customer');
        $report->setRiskScore(50);
        $report->setStatus('needs_attention');

        $array = $report->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('entity_type_code', $array);
        $this->assertArrayHasKey('differences', $array);
        $this->assertArrayHasKey('risk_score', $array);
        $this->assertArrayHasKey('risk_level', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('recommendations', $array);
        $this->assertArrayHasKey('analyzed_at', $array);
    }
}
