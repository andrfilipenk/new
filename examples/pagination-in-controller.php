<?php

class ProductController extends Controller
{
    public function indexAction()
    {
        $page = (int) $this->request->get('page', 1);
        $search = $this->request->get('search', '');
        
        $paginator = DatabasePaginator::fromModel(Product::class)
            ->when($search, fn($q) => $q->search($search, ['name', 'description']))
            ->orderBy('created_at', 'desc')
            ->paginate(20, $page, $this->request->getUri(), $this->request->getQuery());
        
        // API response
        if ($this->request->isAjax()) {
            return $this->json($paginator->toArray());
        }
        
        // HTML response
        return $this->render('products/index', [
            'products' => $paginator->items(),
            'pagination' => PaginatorView::make($paginator, ['view' => 'bootstrap'])
        ]);
    }
}



// via view rendering
use Core\Pagination\PaginatorView;

// Default pagination
$view = PaginatorView::make($paginator);
echo $view->render();

// Bootstrap style
$view = PaginatorView::make($paginator, ['view' => 'bootstrap']);
echo $view->render();

// Simple (prev/next only)
$view = PaginatorView::make($paginator, ['view' => 'simple']);
echo $view->render();





// array

$arrayPaginator = new ArrayPaginator($users);

// Chain operations
$paginator = $arrayPaginator
    ->filter(fn($user) => $user->active)        // Filter active users
    ->sortBy('name', 'desc')                     // Sort by name
    ->search('john', ['name', 'email'])          // Search specific fields
    ->paginate(10, 1, '/users', ['status' => 'active']);


use Core\Pagination\ArrayPaginator;

$data = range(1, 100);
$paginator = ArrayPaginator::make($data, 10, 2); // 10 per page, page 2

echo "Total: " . $paginator->total();           // 100
echo "Current: " . $paginator->currentPage();   // 2
echo "Items: " . count($paginator->items());    // 10