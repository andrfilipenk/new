<?php

namespace Modules\PIM\Models;

use Core\ORM\Model;
use App\Models\User;

class Product extends Model
{
    protected string $table = 'pim_products';
    
    protected array $fillable = [
        'sku', 'name', 'slug', 'description', 'short_description', 'brand_id',
        'type', 'status', 'price', 'cost', 'compare_at_price', 'tax_class',
        'weight', 'weight_unit', 'dimensions', 'dimension_unit', 'main_image',
        'is_featured', 'track_inventory', 'stock_quantity', 'low_stock_threshold',
        'view_count', 'meta_title', 'meta_description', 'meta_keywords', 'created_by'
    ];
    
    protected array $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
        'view_count' => 'integer',
        'is_featured' => 'boolean',
        'track_inventory' => 'boolean'
    ];
    
    protected bool $useSoftDeletes = true;
    
    /**
     * Get the brand
     */
    public function brand(): ?Brand
    {
        if (!$this->brand_id) {
            return null;
        }
        return Brand::find($this->brand_id);
    }
    
    /**
     * Get the creator
     */
    public function creator(): ?User
    {
        if (!$this->created_by) {
            return null;
        }
        return User::find($this->created_by);
    }
    
    /**
     * Get product variants
     */
    public function variants(): array
    {
        return ProductVariant::where('product_id', $this->id)
            ->where('is_active', true)
            ->get();
    }
    
    /**
     * Get product categories
     */
    public function categories(): array
    {
        $pivots = Model::query()
            ->table('pim_product_categories')
            ->where('product_id', $this->id)
            ->get();
        
        $categories = [];
        foreach ($pivots as $pivot) {
            $category = PimCategory::find($pivot['category_id']);
            if ($category) {
                $categories[] = $category;
            }
        }
        return $categories;
    }
    
    /**
     * Get primary category
     */
    public function primaryCategory(): ?PimCategory
    {
        $pivot = Model::query()
            ->table('pim_product_categories')
            ->where('product_id', $this->id)
            ->where('is_primary', true)
            ->first();
        
        if ($pivot) {
            return PimCategory::find($pivot['category_id']);
        }
        return null;
    }
    
    /**
     * Attach category
     */
    public function attachCategory(int $categoryId, bool $isPrimary = false): void
    {
        $exists = Model::query()
            ->table('pim_product_categories')
            ->where('product_id', $this->id)
            ->where('category_id', $categoryId)
            ->first();
        
        if (!$exists) {
            Model::query()
                ->table('pim_product_categories')
                ->insert([
                    'product_id' => $this->id,
                    'category_id' => $categoryId,
                    'is_primary' => $isPrimary,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        }
    }
    
    /**
     * Get product attributes
     */
    public function attributes(): array
    {
        $pivots = Model::query()
            ->table('pim_product_attributes')
            ->where('product_id', $this->id)
            ->get();
        
        $attributes = [];
        foreach ($pivots as $pivot) {
            $attribute = Attribute::find($pivot['attribute_id']);
            if ($attribute) {
                $attribute->pivot_value = $pivot['value'];
                $attribute->pivot_value_text = $pivot['value_text'];
                $attribute->pivot_value_number = $pivot['value_number'];
                $attribute->pivot_value_date = $pivot['value_date'];
                $attribute->pivot_value_boolean = $pivot['value_boolean'];
                $attributes[] = $attribute;
            }
        }
        return $attributes;
    }
    
    /**
     * Get attribute value
     */
    public function getAttributeValue(string $attributeCode)
    {
        $attribute = Attribute::where('code', $attributeCode)->first();
        if (!$attribute) {
            return null;
        }
        
        $pivot = Model::query()
            ->table('pim_product_attributes')
            ->where('product_id', $this->id)
            ->where('attribute_id', $attribute->id)
            ->first();
        
        if (!$pivot) {
            return null;
        }
        
        $valueColumn = $attribute->getValueColumn();
        return $pivot[$valueColumn] ?? $pivot['value'];
    }
    
    /**
     * Set attribute value
     */
    public function setAttributeValue(int $attributeId, $value): void
    {
        $attribute = Attribute::find($attributeId);
        if (!$attribute) {
            return;
        }
        
        $valueColumn = $attribute->getValueColumn();
        $data = [
            'product_id' => $this->id,
            'attribute_id' => $attributeId,
            'value' => null,
            'value_text' => null,
            'value_number' => null,
            'value_date' => null,
            'value_boolean' => null
        ];
        
        $data[$valueColumn] = $value;
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        $exists = Model::query()
            ->table('pim_product_attributes')
            ->where('product_id', $this->id)
            ->where('attribute_id', $attributeId)
            ->first();
        
        if ($exists) {
            Model::query()
                ->table('pim_product_attributes')
                ->where('product_id', $this->id)
                ->where('attribute_id', $attributeId)
                ->update($data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            Model::query()
                ->table('pim_product_attributes')
                ->insert($data);
        }
    }
    
    /**
     * Get product images
     */
    public function images(): array
    {
        return ProductImage::where('product_id', $this->id)
            ->where('variant_id', null)
            ->orderBy('order', 'ASC')
            ->get();
    }
    
    /**
     * Check if product is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
    
    /**
     * Check if product is out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->track_inventory && $this->stock_quantity <= 0;
    }
    
    /**
     * Check if product is low on stock
     */
    public function isLowStock(): bool
    {
        if (!$this->track_inventory || !$this->low_stock_threshold) {
            return false;
        }
        return $this->stock_quantity <= $this->low_stock_threshold;
    }
    
    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->view_count = ($this->view_count ?? 0) + 1;
        $this->save();
    }
    
    /**
     * Get full URL
     */
    public function getUrl(): string
    {
        return '/products/' . $this->slug;
    }
    
    /**
     * Calculate discount percentage
     */
    public function getDiscountPercent(): float
    {
        if (!$this->compare_at_price || $this->compare_at_price <= $this->price) {
            return 0;
        }
        return round((($this->compare_at_price - $this->price) / $this->compare_at_price) * 100, 2);
    }
}
