<?php
// app/Eav/Tests/StorageStrategyTest.php
namespace Eav\Tests;

use PHPUnit\Framework\TestCase;
use Eav\Storage\VarcharStorageStrategy;
use Eav\Storage\IntStorageStrategy;
use Eav\Storage\DecimalStorageStrategy;
use Eav\Storage\TextStorageStrategy;
use Eav\Storage\DatetimeStorageStrategy;

/**
 * Storage Strategy Tests
 */
class StorageStrategyTest extends TestCase
{
    public function testVarcharValidation()
    {
        $db = $this->createMock(\Core\Database\Database::class);
        $strategy = new VarcharStorageStrategy($db, 'eav_values_varchar');

        $this->assertTrue($strategy->validateValue('test string'));
        $this->assertTrue($strategy->validateValue('123'));
        $this->assertFalse($strategy->validateValue(str_repeat('a', 256))); // Too long
    }

    public function testVarcharTransformation()
    {
        $db = $this->createMock(\Core\Database\Database::class);
        $strategy = new VarcharStorageStrategy($db, 'eav_values_varchar');

        $this->assertEquals('test', $strategy->transformForStorage('test'));
        $this->assertEquals('123', $strategy->transformForStorage(123));
        $this->assertEquals('test', $strategy->transformFromStorage('test'));
    }

    public function testIntValidation()
    {
        $db = $this->createMock(\Core\Database\Database::class);
        $strategy = new IntStorageStrategy($db, 'eav_values_int');

        $this->assertTrue($strategy->validateValue(123));
        $this->assertTrue($strategy->validateValue('456'));
        $this->assertFalse($strategy->validateValue(12.5));
        $this->assertFalse($strategy->validateValue('not a number'));
    }

    public function testIntTransformation()
    {
        $db = $this->createMock(\Core\Database\Database::class);
        $strategy = new IntStorageStrategy($db, 'eav_values_int');

        $this->assertEquals(123, $strategy->transformForStorage('123'));
        $this->assertEquals(456, $strategy->transformForStorage(456));
        $this->assertEquals(100, $strategy->transformFromStorage(100));
    }

    public function testDecimalValidation()
    {
        $db = $this->createMock(\Core\Database\Database::class);
        $strategy = new DecimalStorageStrategy($db, 'eav_values_decimal');

        $this->assertTrue($strategy->validateValue(12.34));
        $this->assertTrue($strategy->validateValue('56.78'));
        $this->assertTrue($strategy->validateValue(100));
        $this->assertFalse($strategy->validateValue('not a number'));
    }

    public function testDecimalTransformation()
    {
        $db = $this->createMock(\Core\Database\Database::class);
        $strategy = new DecimalStorageStrategy($db, 'eav_values_decimal');

        $this->assertEquals(12.34, $strategy->transformForStorage(12.34));
        $this->assertEquals(12.3457, $strategy->transformForStorage(12.34567)); // Rounded to 4 decimals
    }

    public function testTextValidation()
    {
        $db = $this->createMock(\Core\Database\Database::class);
        $strategy = new TextStorageStrategy($db, 'eav_values_text');

        $this->assertTrue($strategy->validateValue('short text'));
        $this->assertTrue($strategy->validateValue(str_repeat('a', 10000)));
        $this->assertFalse($strategy->validateValue(123)); // Not a string
    }

    public function testDatetimeValidation()
    {
        $db = $this->createMock(\Core\Database\Database::class);
        $strategy = new DatetimeStorageStrategy($db, 'eav_values_datetime');

        $this->assertTrue($strategy->validateValue(new \DateTime()));
        $this->assertTrue($strategy->validateValue('2024-01-15 10:30:00'));
        $this->assertFalse($strategy->validateValue('invalid date'));
    }

    public function testDatetimeTransformation()
    {
        $db = $this->createMock(\Core\Database\Database::class);
        $strategy = new DatetimeStorageStrategy($db, 'eav_values_datetime');

        $date = new \DateTime('2024-01-15 10:30:00');
        $this->assertEquals('2024-01-15 10:30:00', $strategy->transformForStorage($date));

        $stored = $strategy->transformFromStorage('2024-01-15 10:30:00');
        $this->assertInstanceOf(\DateTime::class, $stored);
        $this->assertEquals('2024-01-15 10:30:00', $stored->format('Y-m-d H:i:s'));
    }

    public function testGetBackendType()
    {
        $db = $this->createMock(\Core\Database\Database::class);

        $varchar = new VarcharStorageStrategy($db, 'eav_values_varchar');
        $this->assertEquals('varchar', $varchar->getBackendType());

        $int = new IntStorageStrategy($db, 'eav_values_int');
        $this->assertEquals('int', $int->getBackendType());

        $decimal = new DecimalStorageStrategy($db, 'eav_values_decimal');
        $this->assertEquals('decimal', $decimal->getBackendType());

        $text = new TextStorageStrategy($db, 'eav_values_text');
        $this->assertEquals('text', $text->getBackendType());

        $datetime = new DatetimeStorageStrategy($db, 'eav_values_datetime');
        $this->assertEquals('datetime', $datetime->getBackendType());
    }

    public function testGetTableName()
    {
        $db = $this->createMock(\Core\Database\Database::class);

        $varchar = new VarcharStorageStrategy($db, 'eav_values_varchar');
        $this->assertEquals('eav_values_varchar', $varchar->getTableName());

        $int = new IntStorageStrategy($db, 'eav_values_int');
        $this->assertEquals('eav_values_int', $int->getTableName());
    }
}
