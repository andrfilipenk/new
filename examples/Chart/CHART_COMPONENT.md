# Chart Component Documentation

## 📊 Overview

The Chart component is a comprehensive SVG chart generation system built following super-senior PHP practices. It provides a fluent API for creating interactive, customizable charts with support for multiple chart types, themes, and styling options.

## 🏗 Architecture

### Core Components

```
app/Core/Chart/
├── ChartInterface.php          # Base chart interface
├── AbstractChart.php           # Abstract base implementation
├── ChartBuilder.php           # Fluent API builder
├── ChartFactory.php           # Factory pattern implementation
├── Exception/
│   └── ChartException.php     # Chart-specific exceptions
├── Renderer/
│   └── SvgRenderer.php        # SVG rendering engine
├── Styles/
│   ├── ChartThemes.php        # Predefined themes
│   └── CssGenerator.php       # CSS generation utilities
└── Types/
    ├── BarChart.php           # Bar chart implementation
    ├── LineChart.php          # Line chart implementation
    └── PieChart.php           # Pie chart implementation
```

### Service Provider

```
app/Module/Provider/
└── ChartServiceProvider.php   # DI container registration
```

## 🚀 Quick Start

### Basic Usage

```php
use Core\Chart\ChartBuilder;

// Simple bar chart
$chart = ChartBuilder::bar()
    ->data([
        'labels' => ['Jan', 'Feb', 'Mar', 'Apr'],
        'datasets' => [
            [
                'label' => 'Sales',
                'data' => [120, 190, 300, 500]
            ]
        ]
    ])
    ->title('Monthly Sales')
    ->size(800, 600)
    ->render();

echo $chart; // Outputs SVG string
```

### Quick Methods

```php
// Quick charts for rapid prototyping
$barChart = ChartBuilder::quickBar(
    ['A', 'B', 'C'], 
    [10, 20, 15], 
    'Simple Bar Chart'
);

$lineChart = ChartBuilder::quickLine(
    ['Week 1', 'Week 2', 'Week 3'], 
    [100, 120, 110], 
    'Weekly Progress'
);

$pieChart = ChartBuilder::quickPie(
    ['Chrome', 'Firefox', 'Safari'], 
    [65, 20, 15], 
    'Browser Share'
);
```

## 📈 Chart Types

### 1. Bar Charts

```php
$chart = ChartBuilder::bar()
    ->data([
        'labels' => ['Q1', 'Q2', 'Q3', 'Q4'],
        'datasets' => [
            [
                'label' => 'Revenue',
                'data' => [50000, 75000, 60000, 90000]
            ],
            [
                'label' => 'Profit',
                'data' => [10000, 15000, 12000, 18000]
            ]
        ]
    ])
    ->orientation('horizontal') // or 'vertical'
    ->showValues(true)
    ->render();
```

**Features:**
- Multiple data series
- Horizontal/vertical orientation
- Value labels
- Custom colors per series

### 2. Line Charts

```php
$chart = ChartBuilder::line()
    ->data([
        'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
        'datasets' => [
            [
                'label' => 'Revenue',
                'data' => [1000, 1200, 800, 1500, 2000],
                'fill' => false,
                'smooth' => true
            ]
        ]
    ])
    ->smooth(true)
    ->showPoints(true)
    ->render();
```

**Features:**
- Smooth curves (Bézier)
- Area filling
- Multiple lines
- Point markers
- Value labels

### 3. Pie Charts

```php
$chart = ChartBuilder::pie()
    ->data([
        'labels' => ['Desktop', 'Mobile', 'Tablet'],
        'values' => [60, 30, 10]
    ])
    ->donut(true, 'Total: 100%')
    ->render();
```

**Features:**
- Regular pie or donut mode
- Percentage labels
- Custom center label for donuts
- Interactive slices

## 🎨 Styling & Themes

### Predefined Themes

```php
use Core\Chart\Styles\ChartThemes;

$chart = ChartBuilder::bar()
    ->data($data)
    ->config(ChartThemes::dark())     // Dark theme
    ->render();

// Available themes:
ChartThemes::modern()     // Modern gradient colors
ChartThemes::dark()       // Dark background theme
ChartThemes::minimal()    // Clean minimal design
ChartThemes::corporate()  // Professional business theme
ChartThemes::vibrant()    // Bright vibrant colors
```

### Custom Styling

```php
$chart = ChartBuilder::line()
    ->data($data)
    ->colors(['#667eea', '#764ba2', '#f093fb'])
    ->grid(true, ['color' => '#f0f0f0', 'strokeWidth' => 1])
    ->padding(50, 150, 80, 100)
    ->style('
        .line { stroke-dasharray: 5, 5; }
        .point { fill: #ff6b6b; }
    ')
    ->render();
```

### CSS Generation

```php
use Core\Chart\Styles\CssGenerator;

// Generate CSS for animations
$css = CssGenerator::animations();

// Generate responsive CSS
$responsive = CssGenerator::responsive();

// Generate complete CSS package
$complete = CssGenerator::complete();
```

## 🏭 Factory Pattern

### Using ChartFactory

```php
use Core\Chart\ChartFactory;

$factory = new ChartFactory();

// Create from configuration
$config = [
    'type' => 'bar',
    'width' => 600,
    'height' => 400,
    'data' => [
        'labels' => ['A', 'B', 'C'],
        'datasets' => [['data' => [10, 20, 15]]]
    ]
];

$chart = $factory->create($config);
$svg = $chart->render();
```

### Dependency Injection

```php
// Register in bootstrap or service provider
$di->register(new \Module\Provider\ChartServiceProvider());

// Use in controllers
class DashboardController extends Controller {
    public function indexAction() {
        $factory = $this->getDI()->get('chartFactory');
        $chart = $factory->createChart('bar')
            ->data($data)
            ->render();
        
        return $this->render('dashboard', ['chart' => $chart]);
    }
}
```

## 🎯 Interactive Features

### JavaScript Integration

```php
$chart = ChartBuilder::bar()
    ->data($data)
    ->script('
        document.querySelectorAll(".bar").forEach((bar, index) => {
            bar.addEventListener("click", function() {
                alert("Clicked bar " + (index + 1));
            });
        });
    ')
    ->style('
        .bar:hover { opacity: 0.8; cursor: pointer; }
    ')
    ->render();
```

### CSS Animations

```php
$chart = ChartBuilder::line()
    ->data($data)
    ->style(CssGenerator::forType('line'))
    ->render();
```

## 📱 Responsive Design

```php
// Responsive configuration
$chart = ChartBuilder::bar()
    ->data($data)
    ->style(CssGenerator::responsive())
    ->render();
```

The generated CSS includes:
- Automatic scaling for mobile devices
- Adjusted text sizes
- Conditional legend display
- Touch-friendly interactions

## 🔧 Configuration Options

### Chart Builder Methods

| Method | Description | Example |
|--------|-------------|---------|
| `type(string)` | Set chart type | `->type('bar')` |
| `data(array)` | Set chart data | `->data($datasets)` |
| `size(int, int)` | Set dimensions | `->size(800, 600)` |
| `title(string)` | Set title | `->title('Sales Chart')` |
| `colors(array)` | Set color palette | `->colors(['#ff0000', '#00ff00'])` |
| `grid(bool, array)` | Configure grid | `->grid(true, ['color' => '#eee'])` |
| `legend(bool, string)` | Configure legend | `->legend(true, 'right')` |
| `padding(int, int, int, int)` | Set padding | `->padding(40, 100, 60, 80)` |
| `showValues(bool)` | Show value labels | `->showValues(true)` |
| `smooth(bool)` | Smooth curves (line) | `->smooth(true)` |
| `orientation(string)` | Bar orientation | `->orientation('horizontal')` |
| `donut(bool, string)` | Donut mode (pie) | `->donut(true, 'Total')` |
| `style(string)` | Add custom CSS | `->style('.bar { fill: red; }')` |
| `script(string)` | Add JavaScript | `->script('console.log("chart");')` |

### Data Formats

#### Bar/Line Charts
```php
$data = [
    'labels' => ['Jan', 'Feb', 'Mar'],
    'datasets' => [
        [
            'label' => 'Series 1',
            'data' => [10, 20, 15],
            'fill' => true,        // Line charts only
            'smooth' => true       // Line charts only
        ]
    ]
];
```

#### Pie Charts
```php
$data = [
    'labels' => ['Red', 'Blue', 'Green'],
    'values' => [40, 35, 25]
];
```

## 🔄 Export/Import

### Configuration Export

```php
$builder = ChartBuilder::bar()
    ->data($data)
    ->title('Export Test');

$config = $builder->toConfig();
// Save to database, file, or send to client
```

### Configuration Import

```php
$builder = ChartBuilder::create('bar')
    ->fromConfig($savedConfig);

$chart = $builder->render();
```

## 🛠 Advanced Usage

### Database Integration

```php
use Core\Chart\ChartFactory;

$factory = new ChartFactory();

// Generate chart from database query
$chart = $factory->fromDatabase(
    'SELECT month, revenue FROM sales WHERE year = ?',
    [2024],
    ['type' => 'line', 'title' => 'Monthly Revenue']
);

// Generate chart from model data
$chart = $factory->fromModel(
    Users::class,
    'created_month',
    'count',
    ['type' => 'bar', 'title' => 'User Registrations']
);
```

### Dashboard Creation

```php
$factory = new ChartFactory();

$dashboard = $factory->dashboard([
    [
        'type' => 'bar',
        'data' => $salesData,
        'title' => 'Sales'
    ],
    [
        'type' => 'pie',
        'data' => $marketShare,
        'title' => 'Market Share'
    ],
    [
        'type' => 'line',
        'data' => $growth,
        'title' => 'Growth Trend'
    ]
]);
```

## 📦 Installation

1. **Files are automatically placed in** `app/Core/Chart/`

2. **Register the service provider:**

```php
// In bootstrap.php or service registration
$di->register(new \Module\Provider\ChartServiceProvider());
```

3. **Use in your application:**

```php
$chart = ChartBuilder::bar()
    ->data($yourData)
    ->render();

echo $chart; // Outputs SVG
```

## 🎯 Best Practices

### 1. Performance
- Use appropriate chart types for data size
- Consider caching for complex charts
- Optimize SVG output for large datasets

### 2. Accessibility
- Include meaningful titles and labels
- Use sufficient color contrast
- Provide alternative data representations

### 3. Responsive Design
- Test on various screen sizes
- Use responsive CSS utilities
- Consider mobile-first design

### 4. Data Validation
- Validate data before chart creation
- Handle empty datasets gracefully
- Provide meaningful error messages

## 🔍 Examples

See the following files for complete examples:
- `examples/chart-usage.php` - Basic usage examples
- `examples/chart-controller-example.php` - MVC integration

## 🚀 Production Deployment

### Performance Considerations
- Enable SVG compression
- Use CDN for static chart assets
- Implement chart caching for repeated data
- Consider server-side SVG optimization

### Security
- Sanitize user input in chart data
- Validate configuration parameters
- Escape SVG content properly
- Implement rate limiting for chart generation APIs

## 📈 Future Enhancements

Potential additions:
- Area charts
- Scatter plots
- Bubble charts
- Gantt charts
- Real-time data streaming
- Export to PNG/PDF
- Chart animations timeline
- 3D chart rendering

---

The Chart component follows super-senior PHP practices with clean architecture, dependency injection, proper exception handling, and extensible design patterns. It's production-ready and fully integrated with your framework's existing systems.