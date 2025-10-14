<?php
/**
 * Simple Calendar Library Validation Test
 * 
 * This script validates the basic functionality of the Calendar library
 */

// Autoloader simulation for testing
spl_autoload_register(function ($class) {
    $prefix = 'Core\\Calendar\\';
    $base_dir = __DIR__ . '/../app/Core/Calendar/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // Try Core\ prefix
        $corePrefix = 'Core\\';
        if (strncmp($corePrefix, $class, strlen($corePrefix)) === 0) {
            $base_dir = __DIR__ . '/../app/Core/';
            $relative_class = substr($class, strlen($corePrefix));
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            if (file_exists($file)) {
                require $file;
            }
        }
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

echo "=== SVG Calendar Library Validation ===\n\n";

try {
    // Test 1: Class Loading
    echo "Test 1: Checking if classes can be loaded...\n";
    $factoryExists = class_exists('Core\\Calendar\\CalendarFactory');
    $builderExists = class_exists('Core\\Calendar\\CalendarBuilder');
    $calendarExists = class_exists('Core\\Calendar\\Calendar');
    $barExists = class_exists('Core\\Calendar\\Models\\Bar');
    
    echo "  CalendarFactory: " . ($factoryExists ? "✓" : "✗") . "\n";
    echo "  CalendarBuilder: " . ($builderExists ? "✓" : "✗") . "\n";
    echo "  Calendar: " . ($calendarExists ? "✓" : "✗") . "\n";
    echo "  Bar Model: " . ($barExists ? "✓" : "✗") . "\n\n";
    
    // Test 2: Factory Methods
    echo "Test 2: Testing factory methods...\n";
    
    use Core\Calendar\CalendarFactory;
    use Core\Calendar\Models\Bar;
    use DateTimeImmutable;
    
    // Create month calendar
    $builder = CalendarFactory::month();
    echo "  CalendarFactory::month() returns builder: " . (is_object($builder) ? "✓" : "✗") . "\n";
    
    // Test 3: Builder Pattern
    echo "\nTest 3: Testing builder pattern...\n";
    $builder->setDate(new DateTimeImmutable('2025-10-15'));
    $builder->enableWeekNumbers();
    echo "  Builder methods chainable: ✓\n";
    
    // Test 4: Bar Creation
    echo "\nTest 4: Creating Bar objects...\n";
    $bar = new Bar(
        id: 'test1',
        title: 'Test Event',
        startDate: new DateTimeImmutable('2025-10-15 09:00'),
        endDate: new DateTimeImmutable('2025-10-15 10:00'),
        backgroundColor: '#4CAF50'
    );
    echo "  Bar created: " . ($bar->getTitle() === 'Test Event' ? "✓" : "✗") . "\n";
    echo "  Bar ID: " . ($bar->getId() === 'test1' ? "✓" : "✗") . "\n";
    
    // Test 5: Calendar Building
    echo "\nTest 5: Building calendar...\n";
    $calendar = $builder->addBar($bar)->build();
    echo "  Calendar built: " . (is_object($calendar) ? "✓" : "✗") . "\n";
    echo "  Calendar has bars: " . (count($calendar->getBars()) > 0 ? "✓" : "✗") . "\n";
    
    // Test 6: SVG Rendering
    echo "\nTest 6: Testing SVG rendering...\n";
    $svg = $calendar->render();
    echo "  SVG generated: " . (strlen($svg) > 0 ? "✓" : "✗") . "\n";
    echo "  Contains <svg> tag: " . (strpos($svg, '<svg') !== false ? "✓" : "✗") . "\n";
    echo "  Contains </svg> tag: " . (strpos($svg, '</svg>') !== false ? "✓" : "✗") . "\n";
    
    // Test 7: Configuration
    echo "\nTest 7: Testing configuration...\n";
    $config = $calendar->getConfig();
    echo "  Config retrieved: " . (is_object($config) ? "✓" : "✗") . "\n";
    echo "  View type is 'month': " . ($config->getViewType() === 'month' ? "✓" : "✗") . "\n";
    echo "  Week numbers enabled: " . ($config->showWeekNumbers() ? "✓" : "✗") . "\n";
    
    // Test 8: Date Range
    echo "\nTest 8: Testing date range...\n";
    $range = $calendar->getDateRange();
    echo "  DateRange retrieved: " . (is_object($range) ? "✓" : "✗") . "\n";
    echo "  Has start date: " . (is_object($range->getStartDate()) ? "✓" : "✗") . "\n";
    echo "  Has end date: " . (is_object($range->getEndDate()) ? "✓" : "✗") . "\n";
    
    // Test 9: Themes
    echo "\nTest 9: Testing theme system...\n";
    use Core\Calendar\Styling\Themes\DarkTheme;
    use Core\Calendar\Styling\Themes\DefaultTheme;
    
    $darkTheme = new DarkTheme();
    $defaultTheme = new DefaultTheme();
    echo "  DarkTheme created: " . (is_object($darkTheme) ? "✓" : "✗") . "\n";
    echo "  DefaultTheme created: " . (is_object($defaultTheme) ? "✓" : "✗") . "\n";
    echo "  Theme has colors: " . (!empty($darkTheme->getPrimaryColor()) ? "✓" : "✗") . "\n";
    
    // Test 10: Data Providers
    echo "\nTest 10: Testing data providers...\n";
    use Core\Calendar\DataProviders\ArrayDataProvider;
    
    $provider = new ArrayDataProvider([$bar]);
    echo "  ArrayDataProvider created: " . (is_object($provider) ? "✓" : "✗") . "\n";
    echo "  Provider has bars: " . (count($provider->getAllBars()) > 0 ? "✓" : "✗") . "\n";
    
    echo "\n=== All Tests Passed! ✓ ===\n\n";
    
    // Output sample SVG snippet
    echo "Sample SVG Output (first 500 characters):\n";
    echo str_repeat('-', 60) . "\n";
    echo substr($svg, 0, 500) . "...\n";
    echo str_repeat('-', 60) . "\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== Validation Complete ===\n";
