<?php

namespace Modules\PIM\Models;

use Core\ORM\Model;

class PriceList extends Model
{
    protected string $table = 'pim_price_lists';
    
    protected array $fillable = [
        'name', 'code', 'description', 'currency', 'is_active',
        'priority', 'valid_from', 'valid_to'
    ];
    
    protected array $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'valid_from' => 'date',
        'valid_to' => 'date'
    ];
    
    /**
     * Get price list items
     */
    public function items(): array
    {
        return PriceListItem::where('price_list_id', $this->id)->get();
    }
    
    /**
     * Check if price list is currently valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        $now = date('Y-m-d');
        
        if ($this->valid_from && $now < $this->valid_from) {
            return false;
        }
        
        if ($this->valid_to && $now > $this->valid_to) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get price for a product
     */
    public function getPriceForProduct(int $productId, ?int $variantId = null, int $quantity = 1): ?array
    {
        $query = Model::query()
            ->table('pim_price_list_items')
            ->where('price_list_id', $this->id)
            ->where('product_id', $productId)
            ->where('min_quantity', '<=', $quantity)
            ->orderBy('min_quantity', 'DESC');
        
        if ($variantId) {
            $query->where('variant_id', $variantId);
        } else {
            $query->where('variant_id', null);
        }
        
        return $query->first();
    }
}
