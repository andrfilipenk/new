<?php

namespace Modules\ERP\Controllers;

use Core\Request;
use Core\Response;
use Modules\ERP\Models\Inventory\Inventory;
use Modules\ERP\Models\Inventory\Warehouse;
use Modules\ERP\Models\Inventory\StockMovement;
use Core\Validation\Validator;
use App\Services\AuditService;

/**
 * Inventory Controller
 * 
 * Handles stock management, adjustments, and transfers
 */
class InventoryController
{
    /**
     * Display inventory dashboard
     */
    public function index(Request $request): Response
    {
        $warehouseId = $request->get('warehouse_id');
        
        $query = \Core\Database\DB::table('erp_inventory');
        
        if ($warehouseId) {
            $query->where('warehouse_id', '=', $warehouseId);
        }
        
        $inventory = $query->get();
        $inventory = array_map(fn($row) => Inventory::hydrate($row), $inventory);
        
        // Get all warehouses for filter
        $warehouses = Warehouse::all();
        
        // Calculate statistics
        $stats = [
            'total_items' => count($inventory),
            'total_quantity' => array_sum(array_column($inventory, 'quantity')),
            'total_value' => 0,
            'low_stock_items' => 0,
        ];
        
        foreach ($inventory as $item) {
            $stats['total_value'] += ($item['quantity'] * $item['average_cost']);
            if ($item['available_quantity'] < 10) {
                $stats['low_stock_items']++;
            }
        }
        
        if ($request->expectsJson()) {
            return Response::json([
                'inventory' => $inventory,
                'warehouses' => $warehouses,
                'statistics' => $stats,
            ]);
        }
        
        return view('erp/inventory/index', [
            'inventory' => $inventory,
            'warehouses' => $warehouses,
            'stats' => $stats,
            'warehouseId' => $warehouseId,
        ]);
    }
    
    /**
     * Show inventory details
     */
    public function show(Request $request, int $id): Response
    {
        $inventory = Inventory::findOrFail($id);
        
        // Get recent stock movements
        $movements = \Core\Database\DB::table('erp_stock_movements')
            ->where('product_id', '=', $inventory['product_id'])
            ->where('warehouse_id', '=', $inventory['warehouse_id'])
            ->orderBy('movement_date', 'DESC')
            ->limit(20)
            ->get();
        
        if ($request->expectsJson()) {
            return Response::json([
                'inventory' => $inventory,
                'movements' => $movements,
            ]);
        }
        
        return view('erp/inventory/show', [
            'inventory' => $inventory,
            'movements' => $movements,
        ]);
    }
    
    /**
     * Adjust inventory quantity
     */
    public function adjust(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer',
            'warehouse_id' => 'required|integer',
            'quantity' => 'required|integer',
            'type' => 'required|in:add,remove,set',
            'reason' => 'required|max:500',
            'cost' => 'numeric',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return Response::json(['errors' => $validator->errors()], 422);
            }
            return redirect('/erp/inventory')->with('errors', $validator->errors());
        }
        
        try {
            $data = $validator->validated();
            
            // Find or create inventory record
            $inventory = Inventory::where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->first();
            
            if (!$inventory) {
                $inventory = Inventory::create([
                    'product_id' => $data['product_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'available_quantity' => 0,
                    'average_cost' => $data['cost'] ?? 0,
                ]);
            }
            
            $oldQuantity = $inventory['quantity'];
            
            // Perform adjustment
            switch ($data['type']) {
                case 'add':
                    $inventory = Inventory::findOrFail($inventory['id']);
                    $inventory->addStock($data['quantity'], $data['cost'] ?? 0);
                    break;
                    
                case 'remove':
                    $inventory = Inventory::findOrFail($inventory['id']);
                    if (!$inventory->removeStock($data['quantity'])) {
                        throw new \Exception('Insufficient stock available');
                    }
                    break;
                    
                case 'set':
                    $inventory = Inventory::findOrFail($inventory['id']);
                    $diff = $data['quantity'] - $inventory['quantity'];
                    if ($diff > 0) {
                        $inventory->addStock($diff, $data['cost'] ?? 0);
                    } elseif ($diff < 0) {
                        $inventory->removeStock(abs($diff));
                    }
                    break;
            }
            
            // Record stock movement
            StockMovement::create([
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['warehouse_id'],
                'type' => 'adjustment',
                'quantity' => $data['type'] === 'remove' ? -$data['quantity'] : $data['quantity'],
                'movement_date' => date('Y-m-d H:i:s'),
                'reference' => $data['reason'],
                'created_by' => auth()->id(),
            ]);
            
            // Log audit
            AuditService::logUpdate(
                'Inventory',
                $inventory['id'],
                ['quantity' => $oldQuantity],
                ['quantity' => $inventory['quantity']],
                auth()->id()
            );
            
            if ($request->expectsJson()) {
                return Response::json(['inventory' => $inventory, 'message' => 'Inventory adjusted successfully']);
            }
            
            return redirect('/erp/inventory')->with('success', 'Inventory adjusted successfully');
        } catch (\Exception $e) {
            logger()->error('Inventory adjustment failed', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => $e->getMessage()], 500);
            }
            
            return redirect('/erp/inventory')->with('error', $e->getMessage());
        }
    }
    
    /**
     * Transfer stock between warehouses
     */
    public function transfer(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer',
            'from_warehouse_id' => 'required|integer',
            'to_warehouse_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'reason' => 'max:500',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $data = $validator->validated();
            
            // Verify source has enough stock
            $fromInventory = Inventory::where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['from_warehouse_id'])
                ->first();
            
            if (!$fromInventory || $fromInventory['available_quantity'] < $data['quantity']) {
                throw new \Exception('Insufficient stock in source warehouse');
            }
            
            // Remove from source
            $fromInv = Inventory::findOrFail($fromInventory['id']);
            $fromInv->removeStock($data['quantity']);
            
            // Add to destination
            $toInventory = Inventory::where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['to_warehouse_id'])
                ->first();
            
            if (!$toInventory) {
                $toInventory = Inventory::create([
                    'product_id' => $data['product_id'],
                    'warehouse_id' => $data['to_warehouse_id'],
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'available_quantity' => 0,
                    'average_cost' => $fromInventory['average_cost'],
                ]);
            }
            
            $toInv = Inventory::findOrFail($toInventory['id']);
            $toInv->addStock($data['quantity'], $fromInventory['average_cost']);
            
            // Record movements
            StockMovement::create([
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['from_warehouse_id'],
                'type' => 'transfer_out',
                'quantity' => -$data['quantity'],
                'movement_date' => date('Y-m-d H:i:s'),
                'reference' => "Transfer to warehouse #{$data['to_warehouse_id']}: " . ($data['reason'] ?? ''),
                'created_by' => auth()->id(),
            ]);
            
            StockMovement::create([
                'product_id' => $data['product_id'],
                'warehouse_id' => $data['to_warehouse_id'],
                'type' => 'transfer_in',
                'quantity' => $data['quantity'],
                'movement_date' => date('Y-m-d H:i:s'),
                'reference' => "Transfer from warehouse #{$data['from_warehouse_id']}: " . ($data['reason'] ?? ''),
                'created_by' => auth()->id(),
            ]);
            
            return Response::json(['message' => 'Stock transferred successfully']);
        } catch (\Exception $e) {
            logger()->error('Stock transfer failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get low stock items
     */
    public function lowStock(Request $request): Response
    {
        $threshold = $request->get('threshold', 10);
        
        $lowStock = \Core\Database\DB::table('erp_inventory')
            ->where('available_quantity', '<', $threshold)
            ->get();
        
        return Response::json(['low_stock' => $lowStock]);
    }
}
