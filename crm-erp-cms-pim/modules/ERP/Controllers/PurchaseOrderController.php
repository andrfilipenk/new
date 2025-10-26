<?php

namespace Modules\ERP\Controllers;

use Core\Request;
use Core\Response;
use Modules\ERP\Models\Procurement\PurchaseOrder;
use Modules\ERP\Models\Procurement\PurchaseOrderItem;
use Modules\ERP\Models\Procurement\Vendor;
use Modules\ERP\Models\Inventory\Warehouse;
use Core\Validation\Validator;
use App\Services\AuditService;
use App\Services\NotificationService;

/**
 * Purchase Order Controller
 * 
 * Handles procurement management with workflow approval integration
 */
class PurchaseOrderController
{
    /**
     * Display list of purchase orders
     */
    public function index(Request $request): Response
    {
        $status = $request->get('status');
        
        $query = \Core\Database\DB::table('erp_purchase_orders');
        
        if ($status) {
            $query->where('status', '=', $status);
        }
        
        $purchaseOrders = $query->orderBy('order_date', 'DESC')->get();
        $purchaseOrders = array_map(fn($row) => PurchaseOrder::hydrate($row), $purchaseOrders);
        
        if ($request->expectsJson()) {
            return Response::json(['purchase_orders' => $purchaseOrders]);
        }
        
        return view('erp/purchase-orders/index', [
            'purchaseOrders' => $purchaseOrders,
            'status' => $status,
        ]);
    }
    
    /**
     * Show create form
     */
    public function create(): Response
    {
        $vendors = Vendor::all();
        $warehouses = Warehouse::all();
        
        return view('erp/purchase-orders/create', [
            'vendors' => $vendors,
            'warehouses' => $warehouses,
        ]);
    }
    
    /**
     * Store new purchase order
     */
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'vendor_id' => 'required|integer',
            'warehouse_id' => 'required|integer',
            'order_date' => 'required|date',
            'expected_date' => 'required|date',
            'payment_terms' => 'max:200',
            'notes' => 'max:1000',
            'discount_amount' => 'numeric',
            'shipping_cost' => 'numeric',
            'items' => 'required',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return Response::json(['errors' => $validator->errors()], 422);
            }
            return redirect('/erp/purchase-orders/create')->with('errors', $validator->errors());
        }
        
        try {
            $data = $validator->validated();
            
            // Generate PO number
            $data['po_number'] = PurchaseOrder::generatePoNumber();
            $data['status'] = 'draft';
            $data['created_by'] = auth()->id();
            $data['subtotal'] = 0;
            $data['tax_amount'] = 0;
            $data['total_amount'] = 0;
            
            $po = PurchaseOrder::create($data);
            
            // Add items
            $items = json_decode($data['items'], true);
            foreach ($items as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po['id'],
                    'product_id' => $item['product_id'],
                    'description' => $item['description'] ?? '',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'tax_amount' => ($item['quantity'] * $item['unit_price'] * ($item['tax_rate'] ?? 0) / 100),
                    'line_total' => $item['quantity'] * $item['unit_price'],
                    'received_quantity' => 0,
                ]);
            }
            
            // Calculate totals
            $poModel = PurchaseOrder::findOrFail($po['id']);
            $poModel->calculateTotals();
            
            AuditService::logCreate('PurchaseOrder', $po['id'], $data, auth()->id());
            
            if ($request->expectsJson()) {
                return Response::json(['purchase_order' => $po, 'message' => 'Purchase order created successfully'], 201);
            }
            
            return redirect('/erp/purchase-orders/' . $po['id'])->with('success', 'Purchase order created successfully');
        } catch (\Exception $e) {
            logger()->error('Purchase order creation failed', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to create purchase order'], 500);
            }
            
            return redirect('/erp/purchase-orders/create')->with('error', 'Failed to create purchase order');
        }
    }
    
    /**
     * Show single purchase order
     */
    public function show(Request $request, int $id): Response
    {
        $po = PurchaseOrder::findOrFail($id);
        $poModel = PurchaseOrder::findOrFail($id);
        
        $vendor = $poModel->vendor();
        $warehouse = $poModel->warehouse();
        $items = $poModel->items();
        
        if ($request->expectsJson()) {
            return Response::json([
                'purchase_order' => $po,
                'vendor' => $vendor,
                'warehouse' => $warehouse,
                'items' => $items,
            ]);
        }
        
        return view('erp/purchase-orders/show', [
            'po' => $po,
            'vendor' => $vendor,
            'warehouse' => $warehouse,
            'items' => $items,
        ]);
    }
    
    /**
     * Update purchase order
     */
    public function update(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'vendor_id' => 'required|integer',
            'warehouse_id' => 'required|integer',
            'order_date' => 'required|date',
            'expected_date' => 'required|date',
            'payment_terms' => 'max:200',
            'notes' => 'max:1000',
            'discount_amount' => 'numeric',
            'shipping_cost' => 'numeric',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $po = PurchaseOrder::findOrFail($id);
            
            if ($po['status'] !== 'draft') {
                throw new \Exception('Cannot modify purchase order that is not in draft status');
            }
            
            $oldData = $po;
            $po = PurchaseOrder::findOrFail($id);
            $po->update($validator->validated());
            
            AuditService::logUpdate('PurchaseOrder', $id, $oldData, $validator->validated(), auth()->id());
            
            return Response::json(['purchase_order' => $po, 'message' => 'Purchase order updated successfully']);
        } catch (\Exception $e) {
            logger()->error('Purchase order update failed', ['error' => $e->getMessage(), 'po_id' => $id]);
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Approve purchase order (workflow integration)
     */
    public function approve(Request $request, int $id): Response
    {
        try {
            $po = PurchaseOrder::findOrFail($id);
            $poModel = PurchaseOrder::findOrFail($id);
            
            if ($po['status'] !== 'draft' && $po['status'] !== 'pending') {
                throw new \Exception('Purchase order cannot be approved');
            }
            
            $poModel->approve(auth()->id());
            
            // Notify creator
            if ($po['created_by']) {
                NotificationService::send(
                    $po['created_by'],
                    'Purchase Order Approved',
                    "Purchase order {$po['po_number']} has been approved.",
                    'success'
                );
            }
            
            AuditService::logUpdate('PurchaseOrder', $id, ['status' => 'draft'], ['status' => 'sent'], auth()->id());
            
            return Response::json(['message' => 'Purchase order approved successfully']);
        } catch (\Exception $e) {
            logger()->error('Purchase order approval failed', ['error' => $e->getMessage(), 'po_id' => $id]);
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Reject purchase order
     */
    public function reject(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|max:500',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $po = PurchaseOrder::findOrFail($id);
            $po->update([
                'status' => 'rejected',
                'notes' => ($po['notes'] ?? '') . "\nRejection reason: " . $validator->validated()['reason'],
            ]);
            
            // Notify creator
            if ($po['created_by']) {
                NotificationService::send(
                    $po['created_by'],
                    'Purchase Order Rejected',
                    "Purchase order {$po['po_number']} has been rejected.",
                    'danger'
                );
            }
            
            return Response::json(['message' => 'Purchase order rejected']);
        } catch (\Exception $e) {
            logger()->error('Purchase order rejection failed', ['error' => $e->getMessage(), 'po_id' => $id]);
            return Response::json(['error' => 'Failed to reject purchase order'], 500);
        }
    }
    
    /**
     * Receive purchase order items
     */
    public function receive(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $po = PurchaseOrder::findOrFail($id);
            
            $receivedItems = json_decode($validator->validated()['items'], true);
            
            foreach ($receivedItems as $itemData) {
                $item = PurchaseOrderItem::find($itemData['id']);
                if ($item) {
                    $item->update([
                        'received_quantity' => $item['received_quantity'] + $itemData['quantity'],
                    ]);
                    
                    // Update inventory
                    $inventory = \Modules\ERP\Models\Inventory\Inventory::where('product_id', $item['product_id'])
                        ->where('warehouse_id', $po['warehouse_id'])
                        ->first();
                    
                    if ($inventory) {
                        $invModel = \Modules\ERP\Models\Inventory\Inventory::findOrFail($inventory['id']);
                        $invModel->addStock($itemData['quantity'], $item['unit_price']);
                    }
                }
            }
            
            // Check if fully received
            $poModel = PurchaseOrder::findOrFail($id);
            if ($poModel->isFullyReceived()) {
                $po->update([
                    'status' => 'completed',
                    'received_date' => date('Y-m-d'),
                ]);
            }
            
            return Response::json(['message' => 'Items received successfully']);
        } catch (\Exception $e) {
            logger()->error('Purchase order receive failed', ['error' => $e->getMessage(), 'po_id' => $id]);
            return Response::json(['error' => 'Failed to receive items'], 500);
        }
    }
}
