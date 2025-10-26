<?php

namespace Modules\ERP\Models\Inventory;

use Core\ORM\Model;

class ErpProduct extends Model
{
    protected string $table = 'erp_products';
    
    protected array $fillable = [
        'sku', 'name', 'description', 'unit', 'cost', 'price',
        'track_inventory', 'reorder_level', 'reorder_quantity',
        'barcode', 'category', 'is_active'
    ];
    
    protected array $casts = [
        'cost' => 'decimal:2',
        'price' => 'decimal:2',
        'track_inventory' => 'boolean',
        'reorder_level' => 'integer',
        'reorder_quantity' => 'integer',
        'is_active' => 'boolean'
    ];
    
    protected bool $useSoftDeletes = true;
    
    /**
     * Get inventory for this product across all warehouses
     */
    public function inventory(): array
    {
        return Inventory::where('product_id', $this->id)->get();
    }
    
    /**
     * Get total stock quantity across all warehouses
     */
    public function getTotalStock(): int
    {
        $total = 0;
        foreach ($this->inventory() as $inv) {
            $total += $inv->available_quantity;
        }
        return $total;
    }
    
    /**
     * Get stock in specific warehouse
     */
    public function getStockInWarehouse(int $warehouseId): ?Inventory
    {
        return Inventory::where('product_id', $this->id)
            ->where('warehouse_id', $warehouseId)
            ->first();
    }
    
    /**
     * Check if product needs reordering
     */
    public function needsReorder(): bool
    {
        if (!$this->track_inventory || !$this->reorder_level) {
            return false;
        }
        return $this->getTotalStock() <= $this->reorder_level;
    }
    
    /**
     * Get stock movements
     */
    public function stockMovements(): array
    {
        return StockMovement::where('product_id', $this->id)
            ->orderBy('movement_date', 'DESC')
            ->get();
    }
    
    /**
     * Calculate profit margin
     */
    public function getProfitMargin(): float
    {
        if ($this->cost <= 0) {
            return 0;
        }
        return round((($this->price - $this->cost) / $this->cost) * 100, 2);
    }
}
