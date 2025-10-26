<?php

namespace Modules\PIM\Models;

use Core\ORM\Model;

class ProductImage extends Model
{
    protected string $table = 'pim_product_images';
    
    protected array $fillable = [
        'product_id', 'variant_id', 'image_path', 'alt_text', 'title', 'order', 'is_main'
    ];
    
    protected array $casts = [
        'order' => 'integer',
        'is_main' => 'boolean'
    ];
    
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
     * Get full URL
     */
    public function getUrl(): string
    {
        return '/storage/' . ltrim($this->image_path, '/');
    }
    
    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrl(int $width = 150, int $height = 150): string
    {
        return "/media/thumbnail?path=" . urlencode($this->image_path) . "&w={$width}&h={$height}";
    }
}
