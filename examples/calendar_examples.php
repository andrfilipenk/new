<?php
/**
 * Calendar Library Usage Examples
 * 
 * This file demonstrates various ways to use the SVG Calendar Library
 */

require_once __DIR__ . '/../bootstrap.php';

use Core\Calendar\CalendarFactory;
use Core\Calendar\Models\Bar;
use Core\Calendar\Styling\Themes\DarkTheme;
use DateTimeImmutable;

// Example 1: Simple Month Calendar
echo "=== Example 1: Simple Month Calendar ===\n\n";

$calendar1 = CalendarFactory::month()
    ->build();

echo "Calendar Name: " . $calendar1->getName() . "\n";
echo "SVG Output:\n";
echo $calendar1->render() . "\n\n";

// Example 2: Month Calendar with Tasks/Events
echo "=== Example 2: Month Calendar with Tasks ===\n\n";

$tasks = [
    new Bar(
        id: 'task1',
        title: 'Project Meeting',
        startDate: new DateTimeImmutable('2025-10-15 09:00:00'),
        endDate: new DateTimeImmutable('2025-10-15 10:30:00'),
        backgroundColor: '#4CAF50'
    ),
    new Bar(
        id: 'task2',
        title: 'Development Sprint',
        startDate: new DateTimeImmutable('2025-10-16 00:00:00'),
        endDate: new DateTimeImmutable('2025-10-18 23:59:59'),
        backgroundColor: '#2196F3'
    ),
    new Bar(
        id: 'task3',
        title: 'Code Review',
        startDate: new DateTimeImmutable('2025-10-20 14:00:00'),
        endDate: new DateTimeImmutable('2025-10-20 15:00:00'),
        backgroundColor: '#FF9800'
    ),
];

$calendar2 = CalendarFactory::month()
    ->addBars($tasks)
    ->enableWeekNumbers()
    ->build();

echo "Calendar with " . count($calendar2->getBars()) . " tasks\n";
echo "HTML Output (with script):\n";
echo $calendar2->toHtml() . "\n\n";

// Example 3: Custom Date Range
echo "=== Example 3: Custom Date Range ===\n\n";

$startDate = new DateTimeImmutable('2025-10-10');
$endDate = new DateTimeImmutable('2025-10-25');

$calendar3 = CalendarFactory::custom($startDate, $endDate)
    ->enableRangeSelection()
    ->onRangeSelect('handleRangeSelect')
    ->build();

echo "Custom range calendar from " . $startDate->format('Y-m-d') . " to " . $endDate->format('Y-m-d') . "\n";
echo "View type: " . $calendar3->getConfig()->getViewType() . "\n\n";

// Example 4: Calendar with Dark Theme
echo "=== Example 4: Dark Theme Calendar ===\n\n";

$calendar4 = CalendarFactory::month()
    ->withTheme(new DarkTheme())
    ->enableClickable()
    ->onDateClick('handleDateClick')
    ->build();

echo "Dark themed calendar\n";
echo "Interactive: " . ($calendar4->getConfig()->isClickable() ? 'Yes' : 'No') . "\n\n";

// Example 5: Week View
echo "=== Example 5: Week View ===\n\n";

$calendar5 = CalendarFactory::week(new DateTimeImmutable('2025-10-15'))
    ->setFirstDayOfWeek(1) // Start week on Monday
    ->build();

echo "Week view starting Monday\n";
echo "Date range: " . $calendar5->getDateRange()->getStartDate()->format('Y-m-d') 
     . " to " . $calendar5->getDateRange()->getEndDate()->format('Y-m-d') . "\n\n";

// Example 6: Year View
echo "=== Example 6: Year View ===\n\n";

$calendar6 = CalendarFactory::year(2025)
    ->build();

echo "Year 2025 calendar\n";
echo "View: " . $calendar6->getConfig()->getViewType() . "\n\n";

echo "=== Examples Complete ===\n";
