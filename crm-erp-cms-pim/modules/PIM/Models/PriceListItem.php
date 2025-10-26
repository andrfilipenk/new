<?php

namespace Modules\PIM\Models;

use Core\ORM\Model;

class PriceListItem extends Model
{
    protected string $table = 'pim_price_list_items';
    
    protected array $fillable = [
        'price_list_id', 'product_id', 'variant_id', 'price', 'cost',
        'discount_percent', 'discount_amount', 'final_price', 'min_quantity'
    ];
    
    protected array $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_price' => 'decimal:2',
        'min_quantity' => 'integer'
    ];
    
    /**
     * Get the price list
     */
    public function priceList(): ?PriceList
    {
        if (!$this->price_list_id) {
            return null;
        }
        return PriceList::find($this->price_list_id);
    }
    
    /**
     * Get the product
     */
    public function product(): ?Product
    {
        if (!$this->product_id) {
            return null;
        }
        return Product::find($this->product_id);
    }
    
    /**
     * Get the variant
     */
    public function variant(): ?ProductVariant
    {
        if (!$this->variant_id) {
            return null;
        }
        return ProductVariant::find($this->variant_id);
    }
    
    /**
     * Calculate final price
     */
    public function calculateFinalPrice(): void
    {
        $finalPrice = $this->price;
        
        if ($this->discount_percent > 0) {
            $finalPrice -= ($this->price * $this->discount_percent / 100);
        }
        
        if ($this->discount_amount > 0) {
            $finalPrice -= $this->discount_amount;
        }
        
        $this->final_price = max(0, $finalPrice);
    }
}
