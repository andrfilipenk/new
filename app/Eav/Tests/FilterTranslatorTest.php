<?php
// app/Eav/Tests/FilterTranslatorTest.php
namespace Eav\Tests;

use PHPUnit\Framework\TestCase;
use Eav\Query\FilterTranslator;
use Eav\Models\Attribute;

/**
 * Filter Translator Tests
 */
class FilterTranslatorTest extends TestCase
{
    private function createMockAttribute($code, $backendType)
    {
        $attr = $this->createMock(Attribute::class);
        $attr->id = 1;
        $attr->attribute_code = $code;
        $attr->backend_type = $backendType;
        return $attr;
    }

    public function testEqualsOperator()
    {
        $translator = new FilterTranslator();
        $attributes = [$this->createMockAttribute('name', 'varchar')];

        $filters = [
            ['attribute' => 'name', 'operator' => '=', 'value' => 'test']
        ];

        $result = $translator->translate($filters, $attributes);

        $this->assertCount(1, $result['conditions']);
        $this->assertStringContainsString('val_1.value = ?', $result['conditions'][0]);
        $this->assertEquals(['test'], $result['bindings']);
    }

    public function testGreaterThanOperator()
    {
        $translator = new FilterTranslator();
        $attributes = [$this->createMockAttribute('price', 'decimal')];

        $filters = [
            ['attribute' => 'price', 'operator' => '>', 'value' => 50]
        ];

        $result = $translator->translate($filters, $attributes);

        $this->assertCount(1, $result['conditions']);
        $this->assertStringContainsString('val_1.value > ?', $result['conditions'][0]);
        $this->assertEquals([50], $result['bindings']);
    }

    public function testLikeOperator()
    {
        $translator = new FilterTranslator();
        $attributes = [$this->createMockAttribute('name', 'varchar')];

        $filters = [
            ['attribute' => 'name', 'operator' => 'LIKE', 'value' => '%test%']
        ];

        $result = $translator->translate($filters, $attributes);

        $this->assertCount(1, $result['conditions']);
        $this->assertStringContainsString('LIKE', $result['conditions'][0]);
        $this->assertEquals(['%test%'], $result['bindings']);
    }

    public function testInOperator()
    {
        $translator = new FilterTranslator();
        $attributes = [$this->createMockAttribute('category', 'varchar')];

        $filters = [
            ['attribute' => 'category', 'operator' => 'IN', 'value' => ['A', 'B', 'C']]
        ];

        $result = $translator->translate($filters, $attributes);

        $this->assertCount(1, $result['conditions']);
        $this->assertStringContainsString('IN', $result['conditions'][0]);
        $this->assertEquals(['A', 'B', 'C'], $result['bindings']);
    }

    public function testBetweenOperator()
    {
        $translator = new FilterTranslator();
        $attributes = [$this->createMockAttribute('price', 'decimal')];

        $filters = [
            ['attribute' => 'price', 'operator' => 'BETWEEN', 'value' => [10, 100]]
        ];

        $result = $translator->translate($filters, $attributes);

        $this->assertCount(1, $result['conditions']);
        $this->assertStringContainsString('BETWEEN', $result['conditions'][0]);
        $this->assertEquals([10, 100], $result['bindings']);
    }

    public function testMultipleFilters()
    {
        $translator = new FilterTranslator();
        $attributes = [
            $this->createMockAttribute('name', 'varchar'),
            $this->createMockAttribute('price', 'decimal')
        ];
        $attributes[1]->id = 2;

        $filters = [
            ['attribute' => 'name', 'operator' => '=', 'value' => 'test'],
            ['attribute' => 'price', 'operator' => '>', 'value' => 50]
        ];

        $result = $translator->translate($filters, $attributes);

        $this->assertCount(2, $result['conditions']);
        $this->assertCount(2, $result['bindings']);
        $this->assertEquals(['test', 50], $result['bindings']);
    }

    public function testComplexAndFilter()
    {
        $translator = new FilterTranslator();
        $attributes = [
            $this->createMockAttribute('name', 'varchar'),
            $this->createMockAttribute('price', 'decimal')
        ];
        $attributes[1]->id = 2;

        $filter = [
            'and' => [
                ['attribute' => 'name', 'operator' => '=', 'value' => 'test'],
                ['attribute' => 'price', 'operator' => '>', 'value' => 50]
            ]
        ];

        $result = $translator->translateComplexFilter($filter, $attributes);

        $this->assertNotEmpty($result['conditions']);
        $this->assertCount(2, $result['bindings']);
    }

    public function testIsNullOperator()
    {
        $translator = new FilterTranslator();
        $attributes = [$this->createMockAttribute('description', 'text')];

        $filters = [
            ['attribute' => 'description', 'operator' => 'IS NULL', 'value' => null]
        ];

        $result = $translator->translate($filters, $attributes);

        $this->assertCount(1, $result['conditions']);
        $this->assertStringContainsString('IS NULL', $result['conditions'][0]);
        $this->assertEmpty($result['bindings']);
    }
}
