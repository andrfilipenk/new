<?php

namespace Modules\PIM\Models;

use Core\ORM\Model;

class Brand extends Model
{
    protected string $table = 'pim_brands';
    
    protected array $fillable = [
        'name', 'slug', 'description', 'logo', 'website', 'is_active'
    ];
    
    protected array $casts = [
        'is_active' => 'boolean'
    ];
    
    /**
     * Get products for this brand
     */
    public function products(): array
    {
        return Product::where('brand_id', $this->id)->get();
    }
    
    /**
     * Get active products count
     */
    public function getActiveProductCount(): int
    {
        return Product::where('brand_id', $this->id)
            ->where('status', 'active')
            ->count();
    }
    
    /**
     * Get full URL
     */
    public function getUrl(): string
    {
        return '/brands/' . $this->slug;
    }
}
