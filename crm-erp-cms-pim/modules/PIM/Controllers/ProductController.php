<?php

namespace Modules\PIM\Controllers;

use Core\Request;
use Core\Response;
use Modules\PIM\Services\ProductService;
use Core\Validation\Validator;

/**
 * Product Controller
 */
class ProductController
{
    private ProductService $productService;
    
    public function __construct()
    {
        $this->productService = new ProductService();
    }
    
    /**
     * List products
     */
    public function index(Request $request): Response
    {
        $query = $request->get('q');
        $filters = [
            'category_id' => $request->get('category_id'),
            'min_price' => $request->get('min_price'),
            'max_price' => $request->get('max_price'),
            'status' => $request->get('status'),
        ];
        
        if ($query) {
            $products = $this->productService->search($query, array_filter($filters));
        } else {
            $products = \Modules\PIM\Models\Product::all();
        }
        
        if ($request->expectsJson()) {
            return Response::json(['products' => $products]);
        }
        
        return view('pim/products/index', ['products' => $products]);
    }
    
    /**
     * Create product
     */
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'sku' => 'required|unique:pim_products',
            'name' => 'required|max:200',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'integer|min:0',
            'status' => 'required|in:active,inactive,draft',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $data = $validator->validated();
            $attributes = $request->get('attributes', []);
            
            $product = $this->productService->create($data, $attributes, auth()->id());
            
            return Response::json([
                'product' => $product,
                'message' => 'Product created successfully'
            ], 201);
        } catch (\Exception $e) {
            logger()->error('Product creation failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to create product'], 500);
        }
    }
    
    /**
     * Update product
     */
    public function update(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'name' => 'max:200',
            'price' => 'numeric|min:0',
            'stock_quantity' => 'integer|min:0',
            'status' => 'in:active,inactive,draft',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $data = $validator->validated();
            $attributes = $request->get('attributes');
            
            $product = $this->productService->update($id, $data, $attributes, auth()->id());
            
            return Response::json(['product' => $product, 'message' => 'Product updated']);
        } catch (\Exception $e) {
            logger()->error('Product update failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to update product'], 500);
        }
    }
    
    /**
     * Delete product
     */
    public function destroy(Request $request, int $id): Response
    {
        try {
            $this->productService->delete($id, auth()->id());
            return Response::json(['message' => 'Product deleted successfully']);
        } catch (\Exception $e) {
            logger()->error('Product deletion failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to delete product'], 500);
        }
    }
    
    /**
     * Update stock
     */
    public function updateStock(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer',
            'type' => 'required|in:set,add,subtract',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $data = $validator->validated();
            $product = $this->productService->updateStock($id, $data['quantity'], $data['type'], auth()->id());
            
            return Response::json(['product' => $product, 'message' => 'Stock updated']);
        } catch (\Exception $e) {
            logger()->error('Stock update failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to update stock'], 500);
        }
    }
    
    /**
     * Assign categories
     */
    public function assignCategories(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'category_ids' => 'required|array',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $this->productService->assignCategories($id, $validator->validated()['category_ids'], auth()->id());
            return Response::json(['message' => 'Categories assigned']);
        } catch (\Exception $e) {
            logger()->error('Category assignment failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to assign categories'], 500);
        }
    }
    
    /**
     * Get catalog statistics
     */
    public function statistics(): Response
    {
        $stats = $this->productService->getCatalogStats();
        return Response::json(['statistics' => $stats]);
    }
}
