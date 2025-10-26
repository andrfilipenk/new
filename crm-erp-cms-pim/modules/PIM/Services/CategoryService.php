<?php

namespace Modules\PIM\Services;

use Modules\PIM\Models\PimCategory;
use App\Services\AuditService;

/**
 * Category Management Service
 */
class CategoryService
{
    /**
     * Create category
     */
    public function create(array $data, ?int $userId = null): PimCategory
    {
        // Generate slug if not provided
        if (!isset($data['slug']) && isset($data['name'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }
        
        $category = PimCategory::create($data);
        
        AuditService::logCreate('Category', $category->id, $data, $userId);
        
        return $category;
    }
    
    /**
     * Update category
     */
    public function update(int $categoryId, array $data, ?int $userId = null): PimCategory
    {
        $category = PimCategory::findOrFail($categoryId);
        $oldData = $category->toArray();
        
        $category->update($data);
        
        AuditService::logUpdate('Category', $categoryId, $oldData, $data, $userId);
        
        return $category;
    }
    
    /**
     * Delete category
     */
    public function delete(int $categoryId, ?int $userId = null): bool
    {
        $category = PimCategory::findOrFail($categoryId);
        
        // Move children to parent category
        \Core\Database\DB::table('pim_categories')
            ->where('parent_id', '=', $categoryId)
            ->update(['parent_id' => $category->parent_id]);
        
        // Remove product associations
        \Core\Database\DB::table('pim_product_categories')
            ->where('category_id', '=', $categoryId)
            ->delete();
        
        AuditService::logDelete('Category', $categoryId, $category->toArray(), $userId);
        
        return $category->delete();
    }
    
    /**
     * Build category tree
     */
    public function buildTree(?int $parentId = null): array
    {
        $categories = PimCategory::query()
            ->where('parent_id', $parentId === null ? 'IS NULL' : '=', $parentId)
            ->orderBy('name', 'ASC')
            ->get();
        
        $tree = [];
        
        foreach ($categories as $categoryData) {
            $category = PimCategory::hydrate($categoryData);
            $tree[] = [
                'category' => $category,
                'children' => $this->buildTree($category->id),
                'product_count' => $this->getProductCount($category->id),
            ];
        }
        
        return $tree;
    }
    
    /**
     * Get product count for category
     */
    public function getProductCount(int $categoryId, bool $includeChildren = false): int
    {
        $count = \Core\Database\DB::table('pim_product_categories')
            ->where('category_id', '=', $categoryId)
            ->count();
        
        if ($includeChildren) {
            $children = PimCategory::query()
                ->where('parent_id', '=', $categoryId)
                ->get();
            
            foreach ($children as $child) {
                $childCategory = PimCategory::hydrate($child);
                $count += $this->getProductCount($childCategory->id, true);
            }
        }
        
        return $count;
    }
    
    /**
     * Move category
     */
    public function move(int $categoryId, ?int $newParentId, ?int $userId = null): PimCategory
    {
        $category = PimCategory::findOrFail($categoryId);
        
        // Prevent circular references
        if ($newParentId && $this->isDescendant($newParentId, $categoryId)) {
            throw new \RuntimeException('Cannot move category to its own descendant');
        }
        
        $category->parent_id = $newParentId;
        $category->save();
        
        AuditService::logUpdate('Category', $categoryId, ['parent_id' => $category->parent_id], ['parent_id' => $newParentId], $userId);
        
        return $category;
    }
    
    /**
     * Check if category is descendant of another
     */
    private function isDescendant(int $potentialDescendantId, int $ancestorId): bool
    {
        $category = PimCategory::find($potentialDescendantId);
        
        if (!$category) {
            return false;
        }
        
        if ($category->parent_id === $ancestorId) {
            return true;
        }
        
        if ($category->parent_id) {
            return $this->isDescendant($category->parent_id, $ancestorId);
        }
        
        return false;
    }
    
    /**
     * Generate slug from name
     */
    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Ensure uniqueness
        $originalSlug = $slug;
        $counter = 1;
        
        while (\Core\Database\DB::table('pim_categories')->where('slug', '=', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}
