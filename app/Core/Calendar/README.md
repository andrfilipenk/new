# SVG Calendar Library

An enterprise-grade, high-performance calendar visualization library that generates responsive calendar views using SVG rendering.

## Features

- **Multiple View Types**: Day, Week, Multi-Week, Month, Year, and Custom Period views
- **SVG Rendering**: Crisp visuals at any screen resolution
- **Interactive**: Date selection, range selection, and clickable events
- **Customizable Themes**: Pre-built themes (Default, Dark, Minimal, Colorful) or create your own
- **Bar Overlays**: Display tasks, events, or any time-based data
- **Data Providers**: Array, Database, or Custom callback-based data sources
- **Fluent API**: Intuitive builder pattern for easy configuration
- **Framework Integration**: Native DI container support

## Installation

The library is located in `app/Core/Calendar` and can be registered via the Service Provider.

Add to your `bootstrap.php`:

```php
use Core\Calendar\CalendarServiceProvider;

$providers = [
    // ... other providers
    CalendarServiceProvider::class,
];
```

## Quick Start

### Basic Month Calendar

```php
use Core\Calendar\CalendarFactory;

$calendar = CalendarFactory::month()->build();
echo $calendar->render(); // Outputs SVG
```

### Month Calendar with Tasks

```php
use Core\Calendar\CalendarFactory;
use Core\Calendar\Models\Bar;
use DateTimeImmutable;

$tasks = [
    new Bar(
        id: 'task1',
        title: 'Team Meeting',
        startDate: new DateTimeImmutable('2025-10-15 09:00'),
        endDate: new DateTimeImmutable('2025-10-15 10:30'),
        backgroundColor: '#4CAF50'
    ),
];

$calendar = CalendarFactory::month()
    ->addBars($tasks)
    ->enableWeekNumbers()
    ->build();

echo $calendar->toHtml(); // Outputs SVG + JavaScript
```

### Custom Date Range

```php
$calendar = CalendarFactory::custom(
    new DateTimeImmutable('2025-10-01'),
    new DateTimeImmutable('2025-10-31')
)
    ->enableRangeSelection()
    ->onRangeSelect('handleRangeSelection')
    ->build();
```

## View Types

### Month View
```php
$calendar = CalendarFactory::month()
    ->setDate(new DateTimeImmutable('2025-10-15'))
    ->enableWeekNumbers()
    ->build();
```

### Week View
```php
$calendar = CalendarFactory::week()
    ->setFirstDayOfWeek(1) // Monday
    ->build();
```

### Year View
```php
$calendar = CalendarFactory::year(2025)->build();
```

## Theming

### Use a Pre-built Theme

```php
use Core\Calendar\Styling\Themes\DarkTheme;

$calendar = CalendarFactory::month()
    ->withTheme(new DarkTheme())
    ->build();
```

Available themes:
- `DefaultTheme` - Clean blue-based design
- `DarkTheme` - Dark mode with high contrast
- `MinimalTheme` - Subtle colors and minimal borders
- `ColorfulTheme` - Vibrant colors

### Create Custom Theme

```php
use Core\Calendar\Styling\Theme;

class MyTheme extends Theme
{
    protected string $primaryColor = '#ff0000';
    protected string $backgroundColor = '#ffffff';
    // ... customize other properties
}

$calendar = CalendarFactory::month()
    ->withTheme(new MyTheme())
    ->build();
```

## Data Providers

### Array Data Provider (default)

```php
$bars = [/* Bar objects */];
$calendar = CalendarFactory::month()
    ->addBars($bars)
    ->build();
```

### Database Data Provider

```php
use Core\Calendar\DataProviders\DatabaseDataProvider;

$provider = new DatabaseDataProvider(
    db: $container->get('database'),
    tableName: 'events',
    fieldMapping: [
        'id' => 'event_id',
        'title' => 'event_name',
        'start_date' => 'start_time',
        'end_date' => 'end_time',
    ]
);

$calendar = CalendarFactory::month()
    ->withDataProvider($provider)
    ->build();
```

### Callback Data Provider

```php
use Core\Calendar\DataProviders\CallbackDataProvider;

$provider = new CallbackDataProvider(function($dateRange) {
    // Fetch and return Bar[] for the date range
    return $myService->getEventsForRange($dateRange);
});

$calendar = CalendarFactory::month()
    ->withDataProvider($provider)
    ->build();
```

## Interactivity

### Date Selection

```php
$calendar = CalendarFactory::month()
    ->enableSelection()
    ->onDateClick('handleDateClick')
    ->bindToFormFields(['date' => 'selected_date_field'])
    ->build();
```

JavaScript handler:
```javascript
function handleDateClick(date, element) {
    console.log('Date clicked:', date);
}
```

### Range Selection

```php
$calendar = CalendarFactory::month()
    ->enableRangeSelection()
    ->onRangeSelect('handleRangeSelect')
    ->bindToFormFields([
        'start' => 'start_date_field',
        'end' => 'end_date_field'
    ])
    ->build();
```

### Bar Click Events

```php
$calendar = CalendarFactory::month()
    ->onBarClick('handleBarClick')
    ->build();
```

## Configuration Options

### Builder Methods

| Method | Parameters | Description |
|--------|-----------|-------------|
| `setView()` | string $type | Set view type: day, week, multi-week, month, year, custom |
| `setDate()` | DateTimeInterface | Set calendar date |
| `setDateRange()` | DateTimeInterface $start, $end | Set custom date range |
| `withLocale()` | string $locale | Set locale (default: en_US) |
| `setFirstDayOfWeek()` | int $day | 0=Sunday, 1=Monday, etc. |
| `enableWeekNumbers()` | bool | Show week numbers |
| `enableSelection()` | bool | Enable date selection |
| `enableRangeSelection()` | bool | Enable range selection |
| `withTheme()` | ThemeInterface | Apply theme |
| `addBar()` | Bar | Add single bar overlay |
| `addBars()` | Bar[] | Add multiple bars |
| `onDateClick()` | string $handler | JavaScript function for date clicks |
| `onRangeSelect()` | string $handler | JavaScript function for range selection |
| `setOption()` | string $key, mixed $value | Set custom option |

### View-Specific Options

**Month View:**
```php
->setOption('cellHeight', 100)
->setOption('cellWidth', 120)
->setOption('headerHeight', 80)
```

**Multi-Week View:**
```php
->setOption('weekCount', 4)
```

## Bar Overlays

Create bars to display tasks, events, or any time-based data:

```php
use Core\Calendar\Models\Bar;

$bar = new Bar(
    id: 'unique-id',
    title: 'Event Title',
    startDate: new DateTimeImmutable('2025-10-15 09:00'),
    endDate: new DateTimeImmutable('2025-10-15 10:30'),
    color: '#ffffff',              // Text color (optional)
    backgroundColor: '#4CAF50',    // Background color (optional)
    url: '/events/123',            // Click URL (optional)
    clickHandler: 'handleClick',   // JS handler (optional)
    metadata: ['type' => 'meeting'], // Custom data (optional)
    zIndex: 10                     // Stacking order (optional)
);
```

## Complete Example

```php
use Core\Calendar\CalendarFactory;
use Core\Calendar\Models\Bar;
use Core\Calendar\Styling\Themes\DarkTheme;
use DateTimeImmutable;

// Create bars
$meetings = [
    new Bar(
        id: 'meeting1',
        title: 'Sprint Planning',
        startDate: new DateTimeImmutable('2025-10-15'),
        endDate: new DateTimeImmutable('2025-10-15'),
        backgroundColor: '#2196F3'
    ),
    new Bar(
        id: 'meeting2',
        title: 'Code Review',
        startDate: new DateTimeImmutable('2025-10-20'),
        endDate: new DateTimeImmutable('2025-10-20'),
        backgroundColor: '#FF9800'
    ),
];

// Build calendar
$calendar = CalendarFactory::month()
    ->setDate(new DateTimeImmutable('2025-10-15'))
    ->withTheme(new DarkTheme())
    ->addBars($meetings)
    ->enableWeekNumbers()
    ->enableSelection()
    ->onDateClick('handleDateClick')
    ->setFirstDayOfWeek(1)
    ->setOption('cellHeight', 100)
    ->build();

// Render
echo $calendar->toHtml();
```

## API Reference

### CalendarFactory

Static factory methods for quick calendar creation:

- `CalendarFactory::create(string $name, array $config): CalendarBuilder`
- `CalendarFactory::month(?DateTimeInterface $date): CalendarBuilder`
- `CalendarFactory::week(?DateTimeInterface $date): CalendarBuilder`
- `CalendarFactory::multiWeek(?DateTimeInterface $date, int $weekCount): CalendarBuilder`
- `CalendarFactory::year(?int $year): CalendarBuilder`
- `CalendarFactory::custom(DateTimeInterface $start, DateTimeInterface $end): CalendarBuilder`

### Calendar

Main calendar instance methods:

- `render(): string` - Generate SVG output
- `toHtml(bool $includeScript = true): string` - Generate HTML with optional JavaScript
- `getClientScript(): string` - Get JavaScript for interactivity
- `getBars(): Bar[]` - Get all bars
- `getDateRange(): DateRange` - Get active date range
- `getConfig(): CalendarConfig` - Get configuration

## Architecture

```
app/Core/Calendar/
├── CalendarFactory.php           # Entry point
├── CalendarBuilder.php            # Fluent builder
├── Calendar.php                   # Calendar instance
├── CalendarConfig.php             # Configuration
├── Renderers/                     # View renderers
├── DataProviders/                 # Data sources
├── Models/                        # Data models
├── Styling/                       # Themes & styles
├── Events/                        # Event handling
├── Svg/                          # SVG generation
├── Utilities/                     # Helper classes
├── Exceptions/                    # Custom exceptions
└── CalendarServiceProvider.php   # DI registration
```

## License

Part of the internal framework. See project license.
