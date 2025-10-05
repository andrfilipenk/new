<?php
// examples/pagination-usage.php
// Comprehensive Pagination System Examples

define('BASE_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once __DIR__ . '/../app/bootstrap.php';

use Core\Pagination\Paginator;
use Core\Pagination\ArrayPaginator;
use Core\Pagination\DatabasePaginator;
use Core\Pagination\PaginatorView;
use Module\Admin\Models\Users;

echo "Pagination System Examples\n";
echo "==========================\n\n";

/**
 * Example 1: Basic Array Pagination
 */
function arrayPaginationExample()
{
    echo "1. Array Pagination Example\n";
    echo str_repeat('-', 40) . "\n";
    
    // Sample data
    $data = [];
    for ($i = 1; $i <= 100; $i++) {
        $data[] = [
            'id' => $i,
            'name' => "User {$i}",
            'email' => "user{$i}@example.com",
            'role' => $i <= 10 ? 'admin' : ($i <= 30 ? 'editor' : 'user')
        ];
    }
    
    // Simple pagination
    $paginator = ArrayPaginator::make($data, 10, 2); // 10 per page, page 2
    
    echo "Current Page: " . $paginator->currentPage() . "\n";
    echo "Total Items: " . $paginator->total() . "\n";
    echo "Items on this page: " . count($paginator->items()) . "\n";
    echo "First item: " . $paginator->firstItem() . "\n";
    echo "Last item: " . $paginator->lastItem() . "\n";
    echo "Has next page: " . ($paginator->hasNextPage() ? 'Yes' : 'No') . "\n";
    echo "Has previous page: " . ($paginator->hasPreviousPage() ? 'Yes' : 'No') . "\n";
    
    // Show some items
    echo "\nItems on current page:\n";
    foreach (array_slice($paginator->items(), 0, 3) as $item) {
        echo "- {$item['name']} ({$item['email']})\n";
    }
    echo "...\n\n";
}

/**
 * Example 2: Array Pagination with Filtering and Sorting
 */
function advancedArrayPaginationExample()
{
    echo "2. Advanced Array Pagination (Filter & Sort)\n";
    echo str_repeat('-', 50) . "\n";
    
    // Sample data
    $data = [];
    for ($i = 1; $i <= 50; $i++) {
        $data[] = (object) [
            'id' => $i,
            'name' => "User {$i}",
            'email' => "user{$i}@example.com",
            'role' => $i <= 5 ? 'admin' : ($i <= 15 ? 'editor' : 'user'),
            'active' => $i % 3 !== 0, // Some inactive users
            'created_at' => date('Y-m-d', strtotime("-{$i} days"))
        ];
    }
    
    // Create paginator with filtering and sorting
    $arrayPaginator = new ArrayPaginator($data);
    
    // Filter active users only
    $arrayPaginator->filter(function($user) {
        return $user->active === true;
    });
    
    // Sort by name descending
    $arrayPaginator->sortBy('name', 'desc');
    
    // Paginate
    $paginator = $arrayPaginator->paginate(5, 1, '/users', ['status' => 'active']);
    
    echo "Filtered and sorted pagination:\n";
    echo "Total active users: " . $paginator->total() . "\n";
    echo "Current page: " . $paginator->currentPage() . "\n";
    
    echo "\nActive users (sorted by name desc):\n";
    foreach (array_slice($paginator->items(), 0, 3) as $user) {
        echo "- {$user->name} ({$user->role})\n";
    }
    echo "...\n\n";
}

/**
 * Example 3: Array Pagination with Search
 */
function searchPaginationExample()
{
    echo "3. Array Pagination with Search\n";
    echo str_repeat('-', 40) . "\n";
    
    // Sample data with varied names
    $data = [
        ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
        ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
        ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com'],
        ['id' => 4, 'name' => 'Alice Brown', 'email' => 'alice@example.com'],
        ['id' => 5, 'name' => 'Charlie Davis', 'email' => 'charlie@example.com'],
        ['id' => 6, 'name' => 'Diana Wilson', 'email' => 'diana@example.com'],
        ['id' => 7, 'name' => 'John Anderson', 'email' => 'janderson@example.com'],
    ];
    
    // Search for "john"
    $arrayPaginator = new ArrayPaginator($data);
    $arrayPaginator->search('john', ['name', 'email']);
    
    $paginator = $arrayPaginator->paginate(10, 1, '/search', ['q' => 'john']);
    
    echo "Search results for 'john':\n";
    echo "Found: " . $paginator->total() . " results\n";
    
    foreach ($paginator->items() as $user) {
        echo "- {$user['name']} ({$user['email']})\n";
    }
    echo "\n";
}

/**
 * Example 4: Database Pagination (simulated)
 */
function databasePaginationExample()
{
    echo "4. Database Pagination Example\n";
    echo str_repeat('-', 40) . "\n";
    
    try {
        // This would work with a real database connection
        // For demonstration, we'll show the concept
        
        echo "Database pagination concept:\n";
        echo "```php\n";
        echo "// Create from model\n";
        echo "\$paginator = DatabasePaginator::fromModel(Users::class)\n";
        echo "    ->where('active', 1)\n";
        echo "    ->orderBy('created_at', 'desc')\n";
        echo "    ->search('john', ['name', 'email'])\n";
        echo "    ->paginate(20, 1, '/admin/users', ['status' => 'active']);\n";
        echo "\n";
        echo "// Get results\n";
        echo "foreach (\$paginator->items() as \$user) {\n";
        echo "    echo \$user->name;\n";
        echo "}\n";
        echo "```\n\n";
        
        // Show what the SQL would look like
        echo "Generated SQL would be similar to:\n";
        echo "SELECT * FROM users WHERE active = 1 AND (name LIKE '%john%' OR email LIKE '%john%') ORDER BY created_at DESC LIMIT 20 OFFSET 0\n";
        echo "SELECT COUNT(*) FROM users WHERE active = 1 AND (name LIKE '%john%' OR email LIKE '%john%')\n\n";
        
    } catch (Exception $e) {
        echo "Database not available for demo: " . $e->getMessage() . "\n\n";
    }
}

/**
 * Example 5: Pagination View Rendering
 */
function paginationViewExample()
{
    echo "5. Pagination View Rendering\n";
    echo str_repeat('-', 40) . "\n";
    
    // Create sample paginated data
    $data = range(1, 100);
    $paginator = ArrayPaginator::make($data, 10, 5, '/products', ['category' => 'electronics']);
    
    // Default view
    echo "Default pagination HTML:\n";
    $view = PaginatorView::make($paginator);
    echo htmlspecialchars_decode(strip_tags($view->render())) . "\n\n";
    
    // Simple view
    echo "Simple pagination HTML:\n";
    $simpleView = PaginatorView::make($paginator, ['view' => 'simple']);
    echo htmlspecialchars_decode(strip_tags($simpleView->render())) . "\n\n";
    
    // Bootstrap view
    echo "Bootstrap pagination would include CSS classes:\n";
    $bootstrapView = PaginatorView::make($paginator, ['view' => 'bootstrap']);
    echo "- Uses Bootstrap classes (pagination, page-item, page-link)\n";
    echo "- Responsive design\n";
    echo "- Accessible markup\n\n";
}

/**
 * Example 6: Pagination JSON API Response
 */
function paginationApiExample()
{
    echo "6. Pagination API Response\n";
    echo str_repeat('-', 30) . "\n";
    
    $data = [];
    for ($i = 1; $i <= 25; $i++) {
        $data[] = [
            'id' => $i,
            'title' => "Product {$i}",
            'price' => rand(10, 100),
            'category' => $i <= 10 ? 'electronics' : 'books'
        ];
    }
    
    $paginator = ArrayPaginator::make($data, 5, 2, '/api/products');
    
    echo "API JSON Response:\n";
    echo json_encode($paginator->toArray(), JSON_PRETTY_PRINT) . "\n\n";
}

/**
 * Example 7: Pagination in Controller
 */
function controllerPaginationExample()
{
    echo "7. Pagination in Controller Example\n";
    echo str_repeat('-', 40) . "\n";
    
    echo "Example controller implementation:\n";
    echo "```php\n";
    echo "class ProductController extends Controller\n";
    echo "{\n";
    echo "    public function indexAction()\n";
    echo "    {\n";
    echo "        \$request = \$this->request;\n";
    echo "        \$page = (int) \$request->get('page', 1);\n";
    echo "        \$search = \$request->get('search', '');\n";
    echo "        \$category = \$request->get('category', '');\n";
    echo "\n";
    echo "        // Database pagination\n";
    echo "        \$paginator = DatabasePaginator::fromModel(Product::class)\n";
    echo "            ->when(\$search, fn(\$q) => \$q->search(\$search, ['name', 'description']))\n";
    echo "            ->when(\$category, fn(\$q) => \$q->where('category', \$category))\n";
    echo "            ->orderBy('created_at', 'desc')\n";
    echo "            ->paginate(20, \$page, \$request->getUri(), \$request->getQuery());\n";
    echo "\n";
    echo "        // For API\n";
    echo "        if (\$request->isAjax()) {\n";
    echo "            return \$this->json(\$paginator->toArray());\n";
    echo "        }\n";
    echo "\n";
    echo "        // For HTML view\n";
    echo "        return \$this->render('products/index', [\n";
    echo "            'products' => \$paginator->items(),\n";
    echo "            'pagination' => PaginatorView::make(\$paginator, ['view' => 'bootstrap'])\n";
    echo "        ]);\n";
    echo "    }\n";
    echo "}\n";
    echo "```\n\n";
}

/**
 * Example 8: Custom Pagination Logic
 */
function customPaginationExample()
{
    echo "8. Custom Pagination Implementation\n";
    echo str_repeat('-', 40) . "\n";
    
    // Custom data source
    class CustomDataSource implements \Core\Pagination\PaginatorInterface
    {
        private $data;
        
        public function __construct($data) {
            $this->data = $data;
        }
        
        public function count(): int {
            return count($this->data);
        }
        
        public function getItems(int $offset, int $limit): array {
            return array_slice($this->data, $offset, $limit);
        }
        
        public function paginate(int $perPage = 15, int $currentPage = 1, string $path = '', array $queryParams = [], string $pageName = 'page'): \Core\Pagination\Paginator {
            return new \Core\Pagination\Paginator(
                $this->getItems(($currentPage - 1) * $perPage, $perPage),
                $this->count(),
                $perPage,
                $currentPage,
                $path,
                $queryParams,
                $pageName
            );
        }
    }
    
    $customData = range(1, 50);
    $customSource = new CustomDataSource($customData);
    $paginator = $customSource->paginate(8, 3);
    
    echo "Custom data source pagination:\n";
    echo "Total: " . $paginator->total() . "\n";
    echo "Current page: " . $paginator->currentPage() . "\n";
    echo "Items: " . implode(', ', $paginator->items()) . "\n\n";
}

// Run all examples
try {
    arrayPaginationExample();
    advancedArrayPaginationExample();
    searchPaginationExample();
    databasePaginationExample();
    paginationViewExample();
    paginationApiExample();
    controllerPaginationExample();
    customPaginationExample();
    
    echo "All pagination examples completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}