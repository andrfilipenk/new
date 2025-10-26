<?php

namespace Modules\PIM\Services;

use Modules\PIM\Models\Product;
use Modules\PIM\Models\ProductVariant;
use Modules\PIM\Models\AttributeValue;
use App\Services\AuditService;
use Core\Cache\CacheManager;

/**
 * Product Catalog Service
 */
class ProductService
{
    private CacheManager $cache;
    
    public function __construct()
    {
        $this->cache = CacheManager::getInstance();
    }
    
    /**
     * Create product
     */
    public function create(array $data, ?array $attributes = null, ?int $userId = null): Product
    {
        $product = Product::create($data);
        
        // Set attributes if provided
        if ($attributes) {
            foreach ($attributes as $attributeId => $value) {
                $product->setAttributeValue($attributeId, $value);
            }
        }
        
        AuditService::logCreate('Product', $product->id, $data, $userId);
        
        $this->clearProductCache($product->id);
        
        return $product;
    }
    
    /**
     * Update product
     */
    public function update(int $productId, array $data, ?array $attributes = null, ?int $userId = null): Product
    {
        $product = Product::findOrFail($productId);
        $oldData = $product->toArray();
        
        $product->update($data);
        
        // Update attributes if provided
        if ($attributes) {
            foreach ($attributes as $attributeId => $value) {
                $product->setAttributeValue($attributeId, $value);
            }
        }
        
        AuditService::logUpdate('Product', $productId, $oldData, $data, $userId);
        
        $this->clearProductCache($productId);
        
        return $product;
    }
    
    /**
     * Create product variant
     */
    public function createVariant(int $productId, array $data, ?int $userId = null): ProductVariant
    {
        $data['product_id'] = $productId;
        
        // Generate SKU if not provided
        if (!isset($data['sku'])) {
            $data['sku'] = $this->generateVariantSKU($productId);
        }
        
        $variant = ProductVariant::create($data);
        
        AuditService::logCreate('ProductVariant', $variant->id, $data, $userId);
        
        $this->clearProductCache($productId);
        
        return $variant;
    }
    
    /**
     * Update product variant
     */
    public function updateVariant(int $variantId, array $data, ?int $userId = null): ProductVariant
    {
        $variant = ProductVariant::findOrFail($variantId);
        $oldData = $variant->toArray();
        
        $variant->update($data);
        
        AuditService::logUpdate('ProductVariant', $variantId, $oldData, $data, $userId);
        
        $this->clearProductCache($variant->product_id);
        
        return $variant;
    }
    
    /**
     * Delete product
     */
    public function delete(int $productId, ?int $userId = null): bool
    {
        $product = Product::findOrFail($productId);
        
        // Delete variants
        $variants = $product->variants();
        foreach ($variants as $variant) {
            $variantObj = ProductVariant::hydrate($variant);
            $variantObj->delete();
        }
        
        // Delete attribute values
        \Core\Database\DB::table('pim_attribute_values')
            ->where('product_id', '=', $productId)
            ->delete();
        
        // Delete category relationships
        \Core\Database\DB::table('pim_product_categories')
            ->where('product_id', '=', $productId)
            ->delete();
        
        AuditService::logDelete('Product', $productId, $product->toArray(), $userId);
        
        $this->clearProductCache($productId);
        
        return $product->delete();
    }
    
    /**
     * Assign categories to product
     */
    public function assignCategories(int $productId, array $categoryIds, ?int $userId = null): void
    {
        // Remove existing categories
        \Core\Database\DB::table('pim_product_categories')
            ->where('product_id', '=', $productId)
            ->delete();
        
        // Add new categories
        foreach ($categoryIds as $categoryId) {
            \Core\Database\DB::table('pim_product_categories')->insert([
                'product_id' => $productId,
                'category_id' => $categoryId,
            ]);
        }
        
        $this->clearProductCache($productId);
    }
    
    /**
     * Update product stock
     */
    public function updateStock(int $productId, int $quantity, string $type = 'set', ?int $userId = null): Product
    {
        $product = Product::findOrFail($productId);
        
        $oldQuantity = $product->stock_quantity;
        
        switch ($type) {
            case 'add':
                $product->stock_quantity += $quantity;
                break;
            case 'subtract':
                $product->stock_quantity -= $quantity;
                break;
            default:
                $product->stock_quantity = $quantity;
        }
        
        $product->save();
        
        // Log stock movement
        $this->logStockMovement($productId, $oldQuantity, $product->stock_quantity, $type, $userId);
        
        $this->clearProductCache($productId);
        
        return $product;
    }
    
    /**
     * Update product pricing
     */
    public function updatePricing(int $productId, array $prices, ?int $userId = null): Product
    {
        $product = Product::findOrFail($productId);
        
        if (isset($prices['price'])) {
            $product->price = $prices['price'];
        }
        
        if (isset($prices['sale_price'])) {
            $product->sale_price = $prices['sale_price'];
        }
        
        if (isset($prices['cost_price'])) {
            $product->cost_price = $prices['cost_price'];
        }
        
        $product->save();
        
        $this->clearProductCache($productId);
        
        return $product;
    }
    
    /**
     * Search products
     */
    public function search(string $query, ?array $filters = null): array
    {
        $qb = Product::query();
        
        // Text search
        $qb->where('name', 'LIKE', "%{$query}%")
           ->orWhere('sku', 'LIKE', "%{$query}%")
           ->orWhere('description', 'LIKE', "%{$query}%");
        
        // Apply filters
        if ($filters) {
            if (isset($filters['category_id'])) {
                // Need to join with product_categories table
                $qb->join('pim_product_categories', 'pim_products.id', '=', 'pim_product_categories.product_id')
                   ->where('pim_product_categories.category_id', '=', $filters['category_id']);
            }
            
            if (isset($filters['min_price'])) {
                $qb->where('price', '>=', $filters['min_price']);
            }
            
            if (isset($filters['max_price'])) {
                $qb->where('price', '<=', $filters['max_price']);
            }
            
            if (isset($filters['status'])) {
                $qb->where('status', '=', $filters['status']);
            }
        }
        
        return $qb->get();
    }
    
    /**
     * Get product with full details (cached)
     */
    public function getProductDetails(int $productId): ?Product
    {
        $cacheKey = "product:details:{$productId}";
        
        $productData = $this->cache->get($cacheKey);
        
        if ($productData) {
            return Product::hydrate($productData);
        }
        
        $productArray = \Core\Database\DB::table('pim_products')
            ->where('id', '=', $productId)
            ->first();
        
        if ($productArray) {
            $this->cache->put($cacheKey, $productArray, 1800); // 30 minutes
            return Product::hydrate($productArray);
        }
        
        return null;
    }
    
    /**
     * Generate variant SKU
     */
    private function generateVariantSKU(int $productId): string
    {
        $product = Product::findOrFail($productId);
        $variantCount = count($product->variants());
        
        return $product->sku . '-V' . str_pad($variantCount + 1, 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Log stock movement
     */
    private function logStockMovement(int $productId, int $oldQty, int $newQty, string $type, ?int $userId): void
    {
        // This would integrate with inventory management if available
        logger()->info('Stock movement', [
            'product_id' => $productId,
            'old_quantity' => $oldQty,
            'new_quantity' => $newQty,
            'type' => $type,
            'user_id' => $userId,
        ]);
    }
    
    /**
     * Clear product cache
     */
    private function clearProductCache(int $productId): void
    {
        $this->cache->forget("product:details:{$productId}");
    }
    
    /**
     * Get catalog statistics
     */
    public function getCatalogStats(): array
    {
        $products = Product::all();
        
        $stats = [
            'total_products' => count($products),
            'active_products' => 0,
            'out_of_stock' => 0,
            'low_stock' => 0,
            'total_value' => 0,
            'by_status' => [],
        ];
        
        foreach ($products as $productData) {
            $product = Product::hydrate($productData);
            
            if ($product->status === 'active') {
                $stats['active_products']++;
            }
            
            if ($product->stock_quantity <= 0) {
                $stats['out_of_stock']++;
            } elseif ($product->stock_quantity < 10) {
                $stats['low_stock']++;
            }
            
            $stats['total_value'] += $product->price * $product->stock_quantity;
            
            $status = $product->status;
            if (!isset($stats['by_status'][$status])) {
                $stats['by_status'][$status] = 0;
            }
            $stats['by_status'][$status]++;
        }
        
        return $stats;
    }
}
