<?php

/**
 * Integration tests for Calendar rendering pipeline
 */

// Setup autoloader
spl_autoload_register(function ($class) {
    $prefix = 'Core\\';
    $base_dir = __DIR__ . '/../../app/Core/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

use Core\Calendar\CalendarFactory;
use Core\Calendar\Models\Bar;
use Core\Calendar\Styling\Themes\DarkTheme;
use DateTimeImmutable;

class CalendarIntegrationTest
{
    public static function runAll(): void
    {
        echo "Running Calendar Integration Tests...\n\n";
        
        self::testMonthViewRendering();
        self::testDayViewRendering();
        self::testWeekViewRendering();
        self::testYearViewRendering();
        self::testCalendarWithBars();
        self::testThemeApplication();
        self::testDataProvider();
        
        echo "\n✓ All integration tests passed!\n\n";
    }

    private static function testMonthViewRendering(): void
    {
        $calendar = CalendarFactory::month()->build();
        $svg = $calendar->render();
        
        assert(!empty($svg), 'SVG should not be empty');
        assert(str_contains($svg, '<svg'), 'Should contain SVG opening tag');
        assert(str_contains($svg, '</svg>'), 'Should contain SVG closing tag');
        assert(str_contains($svg, 'calendar-'), 'Should contain calendar class');
        
        echo "✓ Month view rendering test passed\n";
    }

    private static function testDayViewRendering(): void
    {
        $calendar = CalendarFactory::day()->build();
        $svg = $calendar->render();
        
        assert(!empty($svg), 'Day view SVG should not be empty');
        assert(str_contains($svg, '<svg'), 'Should contain SVG tag');
        
        echo "✓ Day view rendering test passed\n";
    }

    private static function testWeekViewRendering(): void
    {
        $calendar = CalendarFactory::week()->build();
        $svg = $calendar->render();
        
        assert(!empty($svg), 'Week view SVG should not be empty');
        assert(str_contains($svg, '<svg'), 'Should contain SVG tag');
        
        echo "✓ Week view rendering test passed\n";
    }

    private static function testYearViewRendering(): void
    {
        $calendar = CalendarFactory::year(2025)->build();
        $svg = $calendar->render();
        
        assert(!empty($svg), 'Year view SVG should not be empty');
        assert(str_contains($svg, '<svg'), 'Should contain SVG tag');
        assert(str_contains($svg, '2025'), 'Should contain year');
        
        echo "✓ Year view rendering test passed\n";
    }

    private static function testCalendarWithBars(): void
    {
        $bar = new Bar(
            id: 'test1',
            title: 'Test Event',
            startDate: new DateTimeImmutable('2025-10-15'),
            endDate: new DateTimeImmutable('2025-10-15'),
            backgroundColor: '#4CAF50'
        );
        
        $calendar = CalendarFactory::month()
            ->addBar($bar)
            ->build();
        
        $bars = $calendar->getBars();
        assert(count($bars) === 1, 'Should have 1 bar');
        assert($bars[0]->getTitle() === 'Test Event', 'Bar title should match');
        
        $svg = $calendar->render();
        assert(!empty($svg), 'Calendar with bars should render');
        
        echo "✓ Calendar with bars test passed\n";
    }

    private static function testThemeApplication(): void
    {
        $calendar = CalendarFactory::month()
            ->withTheme(new DarkTheme())
            ->build();
        
        $svg = $calendar->render();
        assert(!empty($svg), 'Themed calendar should render');
        
        echo "✓ Theme application test passed\n";
    }

    private static function testDataProvider(): void
    {
        use Core\Calendar\DataProviders\ArrayDataProvider;
        
        $bar = new Bar(
            id: 'provider-test',
            title: 'Provider Event',
            startDate: new DateTimeImmutable('2025-10-20'),
            endDate: new DateTimeImmutable('2025-10-20'),
            backgroundColor: '#FF5722'
        );
        
        $provider = new ArrayDataProvider([$bar]);
        
        $calendar = CalendarFactory::month()
            ->withDataProvider($provider)
            ->build();
        
        $bars = $calendar->getBars();
        assert(count($bars) === 1, 'Should have 1 bar from provider');
        assert($bars[0]->getTitle() === 'Provider Event', 'Bar should come from provider');
        
        echo "✓ Data provider test passed\n";
    }
}

// Run tests
try {
    CalendarIntegrationTest::runAll();
} catch (Exception $e) {
    echo "\n✗ Test failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} catch (AssertionError $e) {
    echo "\n✗ Assertion failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
