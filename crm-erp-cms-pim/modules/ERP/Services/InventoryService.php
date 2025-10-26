<?php

namespace Modules\ERP\Services;

use Modules\ERP\Models\Inventory\ErpProduct;
use Modules\ERP\Models\Inventory\Inventory;
use Modules\ERP\Models\Inventory\StockMovement;
use App\Services\AuditService;
use App\Services\NotificationService;

/**
 * Inventory Management Service
 */
class InventoryService
{
    /**
     * Add stock
     */
    public function addStock(int $productId, int $warehouseId, int $quantity, string $reference, ?int $userId = null): StockMovement
    {
        // Update or create stock record
        $stock = Inventory::query()
            ->where('product_id', '=', $productId)
            ->where('warehouse_id', '=', $warehouseId)
            ->first();
        
        if ($stock) {
            $stockObj = Inventory::hydrate($stock);
            $stockObj->quantity += $quantity;
            $stockObj->save();
        } else {
            $stockObj = Inventory::create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'reserved_quantity' => 0,
            ]);
        }
        
        // Create stock movement record
        $movement = StockMovement::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'type' => 'in',
            'quantity' => $quantity,
            'reference' => $reference,
            'user_id' => $userId,
            'movement_date' => date('Y-m-d H:i:s'),
        ]);
        
        AuditService::logCreate('StockMovement', $movement->id, $movement->toArray(), $userId);
        
        return $movement;
    }
    
    /**
     * Remove stock
     */
    public function removeStock(int $productId, int $warehouseId, int $quantity, string $reference, ?int $userId = null): StockMovement
    {
        $stock = Inventory::query()
            ->where('product_id', '=', $productId)
            ->where('warehouse_id', '=', $warehouseId)
            ->first();
        
        if (!$stock) {
            throw new \RuntimeException('Stock record not found');
        }
        
        $stockObj = Inventory::hydrate($stock);
        
        if ($stockObj->quantity < $quantity) {
            throw new \RuntimeException('Insufficient stock');
        }
        
        $stockObj->quantity -= $quantity;
        $stockObj->save();
        
        // Create stock movement record
        $movement = StockMovement::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'type' => 'out',
            'quantity' => $quantity,
            'reference' => $reference,
            'user_id' => $userId,
            'movement_date' => date('Y-m-d H:i:s'),
        ]);
        
        AuditService::logCreate('StockMovement', $movement->id, $movement->toArray(), $userId);
        
        // Check if stock is low
        if ($stockObj->quantity <= $stockObj->min_stock_level) {
            $this->notifyLowStock($productId, $warehouseId, $stockObj->quantity);
        }
        
        return $movement;
    }
    
    /**
     * Transfer stock between warehouses
     */
    public function transferStock(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $quantity,
        string $reference,
        ?int $userId = null
    ): array {
        // Remove from source warehouse
        $outMovement = $this->removeStock($productId, $fromWarehouseId, $quantity, $reference . ' (transfer out)', $userId);
        
        // Add to destination warehouse
        $inMovement = $this->addStock($productId, $toWarehouseId, $quantity, $reference . ' (transfer in)', $userId);
        
        return [
            'out_movement' => $outMovement,
            'in_movement' => $inMovement,
        ];
    }
    
    /**
     * Reserve stock for order
     */
    public function reserveStock(int $productId, int $warehouseId, int $quantity, ?int $userId = null): bool
    {
        $stock = Inventory::query()
            ->where('product_id', '=', $productId)
            ->where('warehouse_id', '=', $warehouseId)
            ->first();
        
        if (!$stock) {
            return false;
        }
        
        $stockObj = Inventory::hydrate($stock);
        
        $available = $stockObj->quantity - $stockObj->reserved_quantity;
        
        if ($available < $quantity) {
            return false;
        }
        
        $stockObj->reserved_quantity += $quantity;
        $stockObj->save();
        
        return true;
    }
    
    /**
     * Release reserved stock
     */
    public function releaseStock(int $productId, int $warehouseId, int $quantity, ?int $userId = null): bool
    {
        $stock = Inventory::query()
            ->where('product_id', '=', $productId)
            ->where('warehouse_id', '=', $warehouseId)
            ->first();
        
        if (!$stock) {
            return false;
        }
        
        $stockObj = Inventory::hydrate($stock);
        $stockObj->reserved_quantity = max(0, $stockObj->reserved_quantity - $quantity);
        $stockObj->save();
        
        return true;
    }
    
    /**
     * Get stock level for product
     */
    public function getStockLevel(int $productId, ?int $warehouseId = null): array
    {
        $query = Inventory::query()->where('product_id', '=', $productId);
        
        if ($warehouseId) {
            $query->where('warehouse_id', '=', $warehouseId);
        }
        
        $stocks = $query->get();
        
        $totalQuantity = 0;
        $totalReserved = 0;
        $byWarehouse = [];
        
        foreach ($stocks as $stockData) {
            $stock = Inventory::hydrate($stockData);
            $totalQuantity += $stock->quantity;
            $totalReserved += $stock->reserved_quantity;
            
            $byWarehouse[$stock->warehouse_id] = [
                'quantity' => $stock->quantity,
                'reserved' => $stock->reserved_quantity,
                'available' => $stock->quantity - $stock->reserved_quantity,
            ];
        }
        
        return [
            'total_quantity' => $totalQuantity,
            'total_reserved' => $totalReserved,
            'total_available' => $totalQuantity - $totalReserved,
            'by_warehouse' => $byWarehouse,
        ];
    }
    
    /**
     * Get low stock products
     */
    public function getLowStockProducts(?int $warehouseId = null): array
    {
        $query = Inventory::query()->where('quantity', '<=', 'min_stock_level');
        
        if ($warehouseId) {
            $query->where('warehouse_id', '=', $warehouseId);
        }
        
        return $query->get();
    }
    
    /**
     * Notify low stock
     */
    private function notifyLowStock(int $productId, int $warehouseId, int $currentQuantity): void
    {
        // Notify admin users or inventory managers
        $product = ErpProduct::findOrFail($productId);
        
        logger()->warning('Low stock alert', [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'current_quantity' => $currentQuantity,
        ]);
    }
}
