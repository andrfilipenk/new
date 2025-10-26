<?php

namespace Modules\PIM\Services;

use Modules\PIM\Models\PriceList;
use Modules\PIM\Models\Product;
use App\Services\AuditService;

/**
 * Pricing Management Service
 */
class PricingService
{
    /**
     * Create price list
     */
    public function createPriceList(array $data, ?int $userId = null): PriceList
    {
        $priceList = PriceList::create($data);
        
        AuditService::logCreate('PriceList', $priceList->id, $data, $userId);
        
        return $priceList;
    }
    
    /**
     * Update price list
     */
    public function updatePriceList(int $priceListId, array $data, ?int $userId = null): PriceList
    {
        $priceList = PriceList::findOrFail($priceListId);
        $oldData = $priceList->toArray();
        
        $priceList->update($data);
        
        AuditService::logUpdate('PriceList', $priceListId, $oldData, $data, $userId);
        
        return $priceList;
    }
    
    /**
     * Get product price for specific price list
     */
    public function getProductPrice(int $productId, ?int $priceListId = null, int $quantity = 1): float
    {
        $product = Product::findOrFail($productId);
        
        // If no price list specified, return base price
        if (!$priceListId) {
            return $product->sale_price ?? $product->price;
        }
        
        $priceList = PriceList::findOrFail($priceListId);
        
        // Get custom price from price list items
        $priceItem = \Core\Database\DB::table('pim_price_list_items')
            ->where('price_list_id', '=', $priceListId)
            ->where('product_id', '=', $productId)
            ->first();
        
        if ($priceItem) {
            return $this->calculatePrice($priceItem['price'], $priceItem['discount_type'], $priceItem['discount_value'], $quantity);
        }
        
        // No custom price, return base price
        return $product->sale_price ?? $product->price;
    }
    
    /**
     * Set product price in price list
     */
    public function setProductPrice(
        int $priceListId,
        int $productId,
        float $price,
        ?string $discountType = null,
        ?float $discountValue = null,
        ?int $userId = null
    ): void {
        // Check if price already exists
        $existing = \Core\Database\DB::table('pim_price_list_items')
            ->where('price_list_id', '=', $priceListId)
            ->where('product_id', '=', $productId)
            ->first();
        
        $data = [
            'price' => $price,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
        ];
        
        if ($existing) {
            \Core\Database\DB::table('pim_price_list_items')
                ->where('id', '=', $existing['id'])
                ->update($data);
        } else {
            $data['price_list_id'] = $priceListId;
            $data['product_id'] = $productId;
            \Core\Database\DB::table('pim_price_list_items')->insert($data);
        }
    }
    
    /**
     * Calculate price with discount
     */
    private function calculatePrice(float $basePrice, ?string $discountType, ?float $discountValue, int $quantity): float
    {
        if (!$discountType || !$discountValue) {
            return $basePrice * $quantity;
        }
        
        $price = $basePrice;
        
        if ($discountType === 'percentage') {
            $price = $basePrice * (1 - ($discountValue / 100));
        } elseif ($discountType === 'fixed') {
            $price = max(0, $basePrice - $discountValue);
        }
        
        return $price * $quantity;
    }
    
    /**
     * Apply bulk pricing update
     */
    public function bulkUpdatePrices(int $priceListId, array $products, ?int $userId = null): int
    {
        $updated = 0;
        
        foreach ($products as $productData) {
            $this->setProductPrice(
                $priceListId,
                $productData['product_id'],
                $productData['price'],
                $productData['discount_type'] ?? null,
                $productData['discount_value'] ?? null,
                $userId
            );
            $updated++;
        }
        
        return $updated;
    }
}
