# Pagination System Documentation

## Overview

A flexible, decoupled pagination system that works with any data source. The system is designed to be simple, powerful, and framework-agnostic.

## Core Components

### 1. `Paginator` Class
The main pagination class that handles all pagination logic and calculations.

**Key Features:**
- Current page management
- URL generation for pagination links
- Page range calculations
- JSON/Array output for APIs
- Comprehensive pagination information

### 2. `PaginatorInterface`
Interface for different data sources to implement pagination.

**Methods:**
- `paginate()` - Main pagination method
- `count()` - Get total item count
- `getItems()` - Get items for specific page

### 3. `ArrayPaginator`
Pagination for array-based data sources.

**Features:**
- Filter support with custom callbacks
- Search functionality across multiple fields
- Sorting by any field (ascending/descending)
- Static `make()` method for quick pagination

### 4. `DatabasePaginator`
Pagination for database queries using QueryBuilder.

**Features:**
- Fluent query building interface
- Automatic count queries
- Custom count queries for complex JOINs
- Model integration
- Search across multiple fields

### 5. `PaginatorView`
HTML rendering for pagination controls.

**View Types:**
- `default` - Full pagination with numbers, prev/next, first/last
- `simple` - Only previous/next buttons
- `compact` - Numbers only
- `bootstrap` - Bootstrap-styled pagination

## Usage Examples

### Basic Array Pagination

```php
use Core\Pagination\ArrayPaginator;

$data = range(1, 100);
$paginator = ArrayPaginator::make($data, 10, 2); // 10 per page, page 2

echo "Total: " . $paginator->total();
echo "Current page: " . $paginator->currentPage();
foreach ($paginator->items() as $item) {
    echo $item;
}
```

### Database Pagination

```php
use Core\Pagination\DatabasePaginator;
use Module\Admin\Models\Users;

$paginator = DatabasePaginator::fromModel(Users::class)
    ->where('active', 1)
    ->orderBy('created_at', 'desc')
    ->search('john', ['name', 'email'])
    ->paginate(20, 1, '/admin/users');

foreach ($paginator->items() as $user) {
    echo $user->name;
}
```

### Array Pagination with Filtering

```php
$arrayPaginator = new ArrayPaginator($users);

// Filter active users
$arrayPaginator->filter(function($user) {
    return $user->active === true;
});

// Sort by name
$arrayPaginator->sortBy('name', 'desc');

// Search in specific fields
$arrayPaginator->search('john', ['name', 'email']);

$paginator = $arrayPaginator->paginate(10, 1);
```

### Rendering Pagination Views

```php
use Core\Pagination\PaginatorView;

// Default view
$view = PaginatorView::make($paginator);
echo $view->render();

// Bootstrap view
$view = PaginatorView::make($paginator, ['view' => 'bootstrap']);
echo $view->render();

// Simple view (prev/next only)
$view = PaginatorView::make($paginator, ['view' => 'simple']);
echo $view->render();
```

### Controller Integration

```php
class ProductController extends Controller
{
    public function indexAction()
    {
        $request = $this->request;
        $page = (int) $request->get('page', 1);
        $search = $request->get('search', '');
        
        $paginator = DatabasePaginator::fromModel(Product::class)
            ->when($search, fn($q) => $q->search($search, ['name', 'description']))
            ->orderBy('created_at', 'desc')
            ->paginate(20, $page, $request->getUri(), $request->getQuery());
        
        // For API responses
        if ($request->isAjax()) {
            return $this->json($paginator->toArray());
        }
        
        // For HTML views
        return $this->render('products/index', [
            'products' => $paginator->items(),
            'pagination' => PaginatorView::make($paginator, ['view' => 'bootstrap'])
        ]);
    }
}
```

### Custom Data Source

```php
class CustomDataSource implements PaginatorInterface
{
    public function paginate(int $perPage = 15, int $currentPage = 1, ...): Paginator
    {
        // Your custom pagination logic
        $items = $this->getCustomData($offset, $limit);
        $total = $this->getCustomCount();
        
        return new Paginator($items, $total, $perPage, $currentPage, ...);
    }
    
    public function count(): int { /* ... */ }
    public function getItems(int $offset, int $limit): array { /* ... */ }
}
```

## API Response Format

```json
{
    "current_page": 2,
    "data": [...],
    "first_page_url": "http://example.com/products?page=1",
    "from": 11,
    "last_page": 10,
    "last_page_url": "http://example.com/products?page=10",
    "next_page_url": "http://example.com/products?page=3",
    "path": "http://example.com/products",
    "per_page": 10,
    "prev_page_url": "http://example.com/products?page=1",
    "to": 20,
    "total": 100
}
```

## Configuration Options

### PaginatorView Options

```php
$options = [
    'view' => 'default', // 'default', 'simple', 'compact', 'bootstrap'
    'previous_text' => '« Previous',
    'next_text' => 'Next »',
    'show_numbers' => true,
    'show_first_last' => true,
    'show_info' => true,
    'on_each_side' => 3,
    'css_classes' => [
        'wrapper' => 'pagination-wrapper',
        'list' => 'pagination',
        'item' => 'page-item',
        'link' => 'page-link',
        'active' => 'active',
        'disabled' => 'disabled',
        'info' => 'pagination-info'
    ]
];
```

## Best Practices

1. **Use appropriate paginator for data source**:
   - `ArrayPaginator` for in-memory data
   - `DatabasePaginator` for database queries

2. **Set reasonable page sizes**:
   - 10-50 items for web pages
   - 100+ for API responses

3. **Include search and filtering**:
   ```php
   $paginator->search($query, ['name', 'email'])
             ->filter($filterCallback);
   ```

4. **Handle edge cases**:
   - Empty results
   - Invalid page numbers
   - Large datasets

5. **Use appropriate view styles**:
   - `simple` for mobile
   - `bootstrap` for responsive design
   - `compact` for limited space

## Performance Considerations

- Database pagination uses LIMIT/OFFSET for efficiency
- Array pagination loads all data into memory
- Use search indexes for database search fields
- Consider caching for expensive count queries
- Use custom count queries for complex JOINs

## Integration Tips

1. **With Models**: Use `DatabasePaginator::fromModel()`
2. **With APIs**: Use `$paginator->toArray()` or `$paginator->toJson()`
3. **With Views**: Use `PaginatorView::make()`
4. **With Filters**: Chain methods for complex filtering