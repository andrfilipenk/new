<?php

namespace Modules\PIM\Models;

use Core\ORM\Model;

class ProductVariant extends Model
{
    protected string $table = 'pim_product_variants';
    
    protected array $fillable = [
        'product_id', 'sku', 'name', 'option_values', 'price', 'cost',
        'compare_at_price', 'weight', 'stock_quantity', 'image', 'barcode', 'is_active'
    ];
    
    protected array $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'stock_quantity' => 'integer',
        'is_active' => 'boolean'
    ];
    
    /**
     * Get the parent product
     */
    public function product(): ?Product
    {
        if (!$this->product_id) {
            return null;
        }
        return Product::find($this->product_id);
    }
    
    /**
     * Get option values as array
     */
    public function getOptionValues(): array
    {
        if (empty($this->option_values)) {
            return [];
        }
        return json_decode($this->option_values, true) ?? [];
    }
    
    /**
     * Set option values from array
     */
    public function setOptionValues(array $options): void
    {
        $this->option_values = json_encode($options);
    }
    
    /**
     * Get variant images
     */
    public function images(): array
    {
        return ProductImage::where('variant_id', $this->id)
            ->orderBy('order', 'ASC')
            ->get();
    }
    
    /**
     * Check if variant is out of stock
     */
    public function isOutOfStock(): bool
    {
        $product = $this->product();
        if (!$product || !$product->track_inventory) {
            return false;
        }
        return $this->stock_quantity <= 0;
    }
    
    /**
     * Get display name (with options)
     */
    public function getDisplayName(): string
    {
        $options = $this->getOptionValues();
        if (empty($options)) {
            return $this->name;
        }
        
        $optionStr = implode(', ', array_map(
            fn($k, $v) => "$k: $v",
            array_keys($options),
            array_values($options)
        ));
        
        return $this->name . ' (' . $optionStr . ')';
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
