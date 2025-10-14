<?php

/**
 * Unit tests for DateCalculator utility class
 */

require_once __DIR__ . '/../../app/Core/Calendar/Utilities/DateCalculator.php';

use Core\Calendar\Utilities\DateCalculator;

class DateCalculatorTest
{
    public static function runAll(): void
    {
        echo "Running DateCalculator Tests...\n\n";
        
        self::testGetFirstDayOfMonth();
        self::testGetLastDayOfMonth();
        self::testGetFirstDayOfWeek();
        self::testGetWeekNumber();
        self::testIsWeekend();
        self::testIsToday();
        self::testGetDayName();
        self::testGetMonthName();
        self::testGetDatesInRange();
        
        echo "\n✓ All DateCalculator tests passed!\n\n";
    }

    private static function testGetFirstDayOfMonth(): void
    {
        $date = new DateTimeImmutable('2025-10-15');
        $first = DateCalculator::getFirstDayOfMonth($date);
        
        assert($first->format('Y-m-d') === '2025-10-01', 'First day of month should be Oct 1');
        echo "✓ getFirstDayOfMonth() test passed\n";
    }

    private static function testGetLastDayOfMonth(): void
    {
        $date = new DateTimeImmutable('2025-10-15');
        $last = DateCalculator::getLastDayOfMonth($date);
        
        assert($last->format('Y-m-d') === '2025-10-31', 'Last day of month should be Oct 31');
        echo "✓ getLastDayOfMonth() test passed\n";
    }

    private static function testGetFirstDayOfWeek(): void
    {
        $date = new DateTimeImmutable('2025-10-15'); // Wednesday
        
        // Sunday as first day (0)
        $first = DateCalculator::getFirstDayOfWeek($date, 0);
        assert($first->format('w') === '0', 'First day should be Sunday');
        
        // Monday as first day (1)
        $first = DateCalculator::getFirstDayOfWeek($date, 1);
        assert($first->format('w') === '1', 'First day should be Monday');
        
        echo "✓ getFirstDayOfWeek() test passed\n";
    }

    private static function testGetWeekNumber(): void
    {
        $date = new DateTimeImmutable('2025-10-15');
        $weekNum = DateCalculator::getWeekNumber($date);
        
        assert(is_int($weekNum) && $weekNum > 0 && $weekNum <= 53, 'Week number should be between 1 and 53');
        echo "✓ getWeekNumber() test passed\n";
    }

    private static function testIsWeekend(): void
    {
        $saturday = new DateTimeImmutable('2025-10-18'); // Saturday
        $sunday = new DateTimeImmutable('2025-10-19'); // Sunday
        $monday = new DateTimeImmutable('2025-10-20'); // Monday
        
        assert(DateCalculator::isWeekend($saturday) === true, 'Saturday should be weekend');
        assert(DateCalculator::isWeekend($sunday) === true, 'Sunday should be weekend');
        assert(DateCalculator::isWeekend($monday) === false, 'Monday should not be weekend');
        
        echo "✓ isWeekend() test passed\n";
    }

    private static function testIsToday(): void
    {
        $today = new DateTimeImmutable();
        $yesterday = $today->modify('-1 day');
        
        assert(DateCalculator::isToday($today) === true, 'Today should be identified correctly');
        assert(DateCalculator::isToday($yesterday) === false, 'Yesterday should not be today');
        
        echo "✓ isToday() test passed\n";
    }

    private static function testGetDayName(): void
    {
        $sunday = DateCalculator::getDayName(0);
        $monday = DateCalculator::getDayName(1);
        $sundayShort = DateCalculator::getDayName(0, 'en_US', true);
        
        assert($sunday === 'Sunday', 'Day 0 should be Sunday');
        assert($monday === 'Monday', 'Day 1 should be Monday');
        assert($sundayShort === 'Sun', 'Short name for Sunday should be Sun');
        
        echo "✓ getDayName() test passed\n";
    }

    private static function testGetMonthName(): void
    {
        $jan = DateCalculator::getMonthName(1);
        $janShort = DateCalculator::getMonthName(1, 'en_US', true);
        $dec = DateCalculator::getMonthName(12);
        
        assert($jan === 'January', 'Month 1 should be January');
        assert($janShort === 'Jan', 'Short name for January should be Jan');
        assert($dec === 'December', 'Month 12 should be December');
        
        echo "✓ getMonthName() test passed\n";
    }

    private static function testGetDatesInRange(): void
    {
        $start = new DateTimeImmutable('2025-10-01');
        $end = new DateTimeImmutable('2025-10-05');
        
        $dates = DateCalculator::getDatesInRange($start, $end);
        
        assert(count($dates) === 5, 'Should return 5 dates');
        assert($dates[0]->format('Y-m-d') === '2025-10-01', 'First date should be Oct 1');
        assert($dates[4]->format('Y-m-d') === '2025-10-05', 'Last date should be Oct 5');
        
        echo "✓ getDatesInRange() test passed\n";
    }
}

// Run tests
try {
    DateCalculatorTest::runAll();
} catch (AssertionError $e) {
    echo "\n✗ Test failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
