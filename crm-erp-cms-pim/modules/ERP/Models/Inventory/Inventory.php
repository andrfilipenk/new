<?php

namespace Modules\ERP\Models\Inventory;

use Core\ORM\Model;

class Inventory extends Model
{
    protected string $table = 'erp_inventory';
    
    protected array $fillable = [
        'product_id', 'warehouse_id', 'quantity', 'reserved_quantity',
        'available_quantity', 'average_cost', 'location'
    ];
    
    protected array $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'available_quantity' => 'integer',
        'average_cost' => 'decimal:2'
    ];
    
    /**
     * Get the product
     */
    public function product(): ?ErpProduct
    {
        if (!$this->product_id) {
            return null;
        }
        return ErpProduct::find($this->product_id);
    }
    
    /**
     * Get the warehouse
     */
    public function warehouse(): ?Warehouse
    {
        if (!$this->warehouse_id) {
            return null;
        }
        return Warehouse::find($this->warehouse_id);
    }
    
    /**
     * Update available quantity
     */
    public function updateAvailableQuantity(): void
    {
        $this->available_quantity = $this->quantity - $this->reserved_quantity;
        $this->save();
    }
    
    /**
     * Reserve quantity
     */
    public function reserve(int $quantity): bool
    {
        if ($quantity > $this->available_quantity) {
            return false;
        }
        
        $this->reserved_quantity += $quantity;
        $this->updateAvailableQuantity();
        return true;
    }
    
    /**
     * Release reserved quantity
     */
    public function releaseReserved(int $quantity): void
    {
        $this->reserved_quantity = max(0, $this->reserved_quantity - $quantity);
        $this->updateAvailableQuantity();
    }
    
    /**
     * Add stock
     */
    public function addStock(int $quantity, float $cost = 0): void
    {
        // Update average cost using weighted average
        if ($cost > 0 && $this->quantity > 0) {
            $totalCost = ($this->quantity * $this->average_cost) + ($quantity * $cost);
            $totalQuantity = $this->quantity + $quantity;
            $this->average_cost = $totalCost / $totalQuantity;
        } elseif ($cost > 0) {
            $this->average_cost = $cost;
        }
        
        $this->quantity += $quantity;
        $this->updateAvailableQuantity();
    }
    
    /**
     * Remove stock
     */
    public function removeStock(int $quantity): bool
    {
        if ($quantity > $this->available_quantity) {
            return false;
        }
        
        $this->quantity -= $quantity;
        $this->updateAvailableQuantity();
        return true;
    }
}
