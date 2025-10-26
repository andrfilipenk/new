<?php

namespace Modules\PIM\Controllers;

use Core\Request;
use Core\Response;
use Modules\PIM\Models\Category;
use Core\Validation\Validator;
use App\Services\AuditService;

/**
 * Category Controller
 * 
 * Handles product category tree structure management
 */
class CategoryController
{
    public function index(Request $request): Response
    {
        $categories = Category::all();
        
        // Build tree structure
        $tree = $this->buildTree($categories);
        
        if ($request->expectsJson()) {
            return Response::json(['categories' => $categories, 'tree' => $tree]);
        }
        
        return view('pim/categories/index', ['categories' => $categories, 'tree' => $tree]);
    }
    
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'parent_id' => 'integer',
            'name' => 'required|max:200',
            'slug' => 'required|max:200',
            'description' => 'max:1000',
            'image' => 'max:255',
            'is_active' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $category = Category::create($validator->validated());
            
            AuditService::logCreate('Category', $category['id'], $validator->validated(), auth()->id());
            
            return Response::json(['category' => $category, 'message' => 'Category created successfully'], 201);
        } catch (\Exception $e) {
            logger()->error('Category creation failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to create category'], 500);
        }
    }
    
    public function update(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'parent_id' => 'integer',
            'name' => 'required|max:200',
            'slug' => 'required|max:200',
            'description' => 'max:1000',
            'is_active' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $category = Category::findOrFail($id);
            $oldData = $category;
            
            $category->update($validator->validated());
            
            AuditService::logUpdate('Category', $id, $oldData, $validator->validated(), auth()->id());
            
            return Response::json(['category' => $category, 'message' => 'Category updated successfully']);
        } catch (\Exception $e) {
            logger()->error('Category update failed', ['error' => $e->getMessage(), 'category_id' => $id]);
            return Response::json(['error' => 'Failed to update category'], 500);
        }
    }
    
    public function destroy(Request $request, int $id): Response
    {
        try {
            $category = Category::findOrFail($id);
            
            // Check for children
            $children = Category::where('parent_id', $id)->get();
            if (count($children) > 0) {
                return Response::json(['error' => 'Cannot delete category with subcategories'], 400);
            }
            
            $category->delete();
            
            return Response::json(['message' => 'Category deleted successfully']);
        } catch (\Exception $e) {
            logger()->error('Category deletion failed', ['error' => $e->getMessage(), 'category_id' => $id]);
            return Response::json(['error' => 'Failed to delete category'], 500);
        }
    }
    
    public function tree(Request $request): Response
    {
        $categories = Category::all();
        $tree = $this->buildTree($categories);
        
        return Response::json(['tree' => $tree]);
    }
    
    private function buildTree(array $categories, ?int $parentId = null): array
    {
        $branch = [];
        
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parentId) {
                $children = $this->buildTree($categories, $category['id']);
                if ($children) {
                    $category['children'] = $children;
                }
                $branch[] = $category;
            }
        }
        
        return $branch;
    }
}
