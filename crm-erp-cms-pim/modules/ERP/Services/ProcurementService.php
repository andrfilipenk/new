<?php

namespace Modules\ERP\Services;

use Modules\ERP\Models\Procurement\PurchaseOrder;
use Modules\ERP\Models\Procurement\PurchaseOrderItem;
use App\Services\AuditService;
use App\Services\NotificationService;

/**
 * Procurement Management Service
 */
class ProcurementService
{
    private InventoryService $inventoryService;
    
    public function __construct()
    {
        $this->inventoryService = new InventoryService();
    }
    
    /**
     * Create purchase order
     */
    public function createPurchaseOrder(array $orderData, array $items, ?int $userId = null): PurchaseOrder
    {
        // Calculate totals
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }
        
        $taxAmount = $subtotal * ($orderData['tax_rate'] ?? 0) / 100;
        $totalAmount = $subtotal + $taxAmount + ($orderData['shipping_cost'] ?? 0);
        
        // Create order
        $order = PurchaseOrder::create(array_merge($orderData, [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'status' => 'draft',
        ]));
        
        // Create order items
        foreach ($items as $itemData) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $order->id,
                'product_id' => $itemData['product_id'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'total' => $itemData['quantity'] * $itemData['unit_price'],
            ]);
        }
        
        AuditService::logCreate('PurchaseOrder', $order->id, $order->toArray(), $userId);
        
        return $order;
    }
    
    /**
     * Submit purchase order for approval
     */
    public function submitForApproval(int $orderId, ?int $userId = null): PurchaseOrder
    {
        $order = PurchaseOrder::findOrFail($orderId);
        
        $order->status = 'pending_approval';
        $order->submitted_at = date('Y-m-d H:i:s');
        $order->submitted_by = $userId;
        $order->save();
        
        // Notify approvers
        $this->notifyApprovers($order);
        
        return $order;
    }
    
    /**
     * Approve purchase order
     */
    public function approve(int $orderId, ?int $userId = null): PurchaseOrder
    {
        $order = PurchaseOrder::findOrFail($orderId);
        
        $order->status = 'approved';
        $order->approved_at = date('Y-m-d H:i:s');
        $order->approved_by = $userId;
        $order->save();
        
        AuditService::logUpdate('PurchaseOrder', $orderId, ['status' => 'pending_approval'], ['status' => 'approved'], $userId);
        
        // Notify requester
        if ($order->submitted_by) {
            NotificationService::send(
                $order->submitted_by,
                'Purchase Order Approved',
                "Purchase order #{$order->order_number} has been approved.",
                'success'
            );
        }
        
        return $order;
    }
    
    /**
     * Reject purchase order
     */
    public function reject(int $orderId, string $reason, ?int $userId = null): PurchaseOrder
    {
        $order = PurchaseOrder::findOrFail($orderId);
        
        $order->status = 'rejected';
        $order->rejected_at = date('Y-m-d H:i:s');
        $order->rejected_by = $userId;
        $order->rejection_reason = $reason;
        $order->save();
        
        // Notify requester
        if ($order->submitted_by) {
            NotificationService::send(
                $order->submitted_by,
                'Purchase Order Rejected',
                "Purchase order #{$order->order_number} has been rejected. Reason: {$reason}",
                'warning'
            );
        }
        
        return $order;
    }
    
    /**
     * Mark as ordered (sent to vendor)
     */
    public function markAsOrdered(int $orderId, ?int $userId = null): PurchaseOrder
    {
        $order = PurchaseOrder::findOrFail($orderId);
        
        if ($order->status !== 'approved') {
            throw new \RuntimeException('Purchase order must be approved before ordering');
        }
        
        $order->status = 'ordered';
        $order->ordered_at = date('Y-m-d H:i:s');
        $order->save();
        
        return $order;
    }
    
    /**
     * Receive goods
     */
    public function receiveGoods(int $orderId, array $receivedItems, int $warehouseId, ?int $userId = null): PurchaseOrder
    {
        $order = PurchaseOrder::findOrFail($orderId);
        
        if ($order->status !== 'ordered') {
            throw new \RuntimeException('Purchase order must be in ordered status to receive goods');
        }
        
        // Process received items
        foreach ($receivedItems as $receivedItem) {
            // Add stock
            $this->inventoryService->addStock(
                $receivedItem['product_id'],
                $warehouseId,
                $receivedItem['quantity'],
                "Purchase Order #{$order->order_number}",
                $userId
            );
            
            // Update received quantity on order item
            \Core\Database\DB::table('erp_purchase_order_items')
                ->where('purchase_order_id', '=', $orderId)
                ->where('product_id', '=', $receivedItem['product_id'])
                ->update(['received_quantity' => $receivedItem['quantity']]);
        }
        
        // Check if all items received
        $allReceived = $this->checkAllItemsReceived($orderId);
        
        if ($allReceived) {
            $order->status = 'received';
            $order->received_at = date('Y-m-d H:i:s');
        } else {
            $order->status = 'partially_received';
        }
        
        $order->save();
        
        return $order;
    }
    
    /**
     * Check if all items are received
     */
    private function checkAllItemsReceived(int $orderId): bool
    {
        $items = PurchaseOrderItem::query()
            ->where('purchase_order_id', '=', $orderId)
            ->get();
        
        foreach ($items as $itemData) {
            $item = PurchaseOrderItem::hydrate($itemData);
            if ($item->received_quantity < $item->quantity) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Close purchase order
     */
    public function close(int $orderId, ?int $userId = null): PurchaseOrder
    {
        $order = PurchaseOrder::findOrFail($orderId);
        
        $order->status = 'closed';
        $order->closed_at = date('Y-m-d H:i:s');
        $order->save();
        
        return $order;
    }
    
    /**
     * Cancel purchase order
     */
    public function cancel(int $orderId, string $reason, ?int $userId = null): PurchaseOrder
    {
        $order = PurchaseOrder::findOrFail($orderId);
        
        if (in_array($order->status, ['received', 'closed'])) {
            throw new \RuntimeException('Cannot cancel received or closed purchase order');
        }
        
        $order->status = 'cancelled';
        $order->cancelled_at = date('Y-m-d H:i:s');
        $order->cancellation_reason = $reason;
        $order->save();
        
        return $order;
    }
    
    /**
     * Notify approvers
     */
    private function notifyApprovers(PurchaseOrder $order): void
    {
        // In a real system, this would notify users with approval permissions
        logger()->info('Purchase order pending approval', ['order_id' => $order->id]);
    }
    
    /**
     * Get procurement statistics
     */
    public function getProcurementStatistics(string $period = 'month'): array
    {
        $startDate = match($period) {
            'today' => date('Y-m-d'),
            'week' => date('Y-m-d', strtotime('-7 days')),
            'month' => date('Y-m-01'),
            'year' => date('Y-01-01'),
            default => date('Y-m-01'),
        };
        
        $orders = PurchaseOrder::query()
            ->where('order_date', '>=', $startDate)
            ->get();
        
        $stats = [
            'total_orders' => count($orders),
            'total_value' => 0,
            'pending_approval' => 0,
            'approved' => 0,
            'ordered' => 0,
            'received' => 0,
        ];
        
        foreach ($orders as $orderData) {
            $order = PurchaseOrder::hydrate($orderData);
            $stats['total_value'] += $order->total_amount;
            
            if (isset($stats[str_replace('-', '_', $order->status)])) {
                $stats[str_replace('-', '_', $order->status)]++;
            }
        }
        
        return $stats;
    }
}
