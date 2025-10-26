<?php

namespace Modules\ERP\Services;

use Modules\ERP\Models\Sales\SalesOrder;
use Modules\ERP\Models\Sales\SalesOrderItem;
use Modules\ERP\Models\Finance\Invoice;
use App\Services\AuditService;
use App\Services\NotificationService;

/**
 * Sales Order Management Service
 */
class SalesService
{
    private InventoryService $inventoryService;
    
    public function __construct()
    {
        $this->inventoryService = new InventoryService();
    }
    
    /**
     * Create sales order
     */
    public function createOrder(array $orderData, array $items, ?int $userId = null): SalesOrder
    {
        // Calculate totals
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }
        
        $taxAmount = $subtotal * ($orderData['tax_rate'] ?? 0) / 100;
        $discountAmount = $orderData['discount_amount'] ?? 0;
        $totalAmount = $subtotal + $taxAmount - $discountAmount + ($orderData['shipping_amount'] ?? 0);
        
        // Create order
        $order = SalesOrder::create(array_merge($orderData, [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'status' => 'pending',
        ]));
        
        // Create order items
        foreach ($items as $itemData) {
            SalesOrderItem::create([
                'sales_order_id' => $order->id,
                'product_id' => $itemData['product_id'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'discount' => $itemData['discount'] ?? 0,
                'tax_rate' => $itemData['tax_rate'] ?? 0,
                'total' => $itemData['quantity'] * $itemData['unit_price'] - ($itemData['discount'] ?? 0),
            ]);
            
            // Reserve stock if warehouse specified
            if (isset($orderData['warehouse_id'])) {
                $this->inventoryService->reserveStock(
                    $itemData['product_id'],
                    $orderData['warehouse_id'],
                    $itemData['quantity'],
                    $userId
                );
            }
        }
        
        AuditService::logCreate('SalesOrder', $order->id, $order->toArray(), $userId);
        
        // Notify sales team
        if (isset($orderData['sales_person_id'])) {
            NotificationService::send(
                $orderData['sales_person_id'],
                'New Sales Order',
                "New sales order #{$order->order_number} created.",
                'info'
            );
        }
        
        return $order;
    }
    
    /**
     * Update order status
     */
    public function updateStatus(int $orderId, string $status, ?int $userId = null): SalesOrder
    {
        $order = SalesOrder::findOrFail($orderId);
        $oldStatus = $order->status;
        
        $order->status = $status;
        $order->save();
        
        AuditService::logUpdate('SalesOrder', $orderId, ['status' => $oldStatus], ['status' => $status], $userId);
        
        return $order;
    }
    
    /**
     * Confirm order
     */
    public function confirmOrder(int $orderId, ?int $userId = null): SalesOrder
    {
        $order = SalesOrder::findOrFail($orderId);
        
        $order->status = 'confirmed';
        $order->confirmed_at = date('Y-m-d H:i:s');
        $order->save();
        
        // Notify customer
        if ($order->customer_id) {
            // Send confirmation email
            logger()->info('Order confirmed', ['order_id' => $orderId]);
        }
        
        return $order;
    }
    
    /**
     * Process order fulfillment
     */
    public function fulfillOrder(int $orderId, int $warehouseId, ?int $userId = null): SalesOrder
    {
        $order = SalesOrder::findOrFail($orderId);
        
        if ($order->status !== 'confirmed') {
            throw new \RuntimeException('Order must be confirmed before fulfillment');
        }
        
        // Get order items
        $items = SalesOrderItem::query()
            ->where('sales_order_id', '=', $orderId)
            ->get();
        
        // Remove stock for each item
        foreach ($items as $itemData) {
            $item = SalesOrderItem::hydrate($itemData);
            
            // Release reserved stock
            $this->inventoryService->releaseStock(
                $item->product_id,
                $warehouseId,
                $item->quantity,
                $userId
            );
            
            // Remove actual stock
            $this->inventoryService->removeStock(
                $item->product_id,
                $warehouseId,
                $item->quantity,
                "Sales Order #{$order->order_number}",
                $userId
            );
        }
        
        $order->status = 'fulfilled';
        $order->fulfilled_at = date('Y-m-d H:i:s');
        $order->save();
        
        return $order;
    }
    
    /**
     * Create invoice from order
     */
    public function createInvoice(int $orderId, ?int $userId = null): Invoice
    {
        $order = SalesOrder::findOrFail($orderId);
        
        $invoice = Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber(),
            'customer_id' => $order->customer_id,
            'invoice_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'subtotal' => $order->subtotal,
            'tax_amount' => $order->tax_amount,
            'discount_amount' => $order->discount_amount,
            'total_amount' => $order->total_amount,
            'status' => 'pending',
            'reference' => "Sales Order #{$order->order_number}",
        ]);
        
        // Update order
        $order->invoice_id = $invoice->id;
        $order->save();
        
        AuditService::logCreate('Invoice', $invoice->id, $invoice->toArray(), $userId);
        
        return $invoice;
    }
    
    /**
     * Cancel order
     */
    public function cancelOrder(int $orderId, string $reason, ?int $userId = null): SalesOrder
    {
        $order = SalesOrder::findOrFail($orderId);
        
        // Release reserved stock
        $items = SalesOrderItem::query()
            ->where('sales_order_id', '=', $orderId)
            ->get();
        
        foreach ($items as $itemData) {
            $item = SalesOrderItem::hydrate($itemData);
            
            if (isset($order->warehouse_id)) {
                $this->inventoryService->releaseStock(
                    $item->product_id,
                    $order->warehouse_id,
                    $item->quantity,
                    $userId
                );
            }
        }
        
        $order->status = 'cancelled';
        $order->cancelled_at = date('Y-m-d H:i:s');
        $order->cancellation_reason = $reason;
        $order->save();
        
        return $order;
    }
    
    /**
     * Get sales statistics
     */
    public function getSalesStatistics(string $period = 'month'): array
    {
        $startDate = match($period) {
            'today' => date('Y-m-d'),
            'week' => date('Y-m-d', strtotime('-7 days')),
            'month' => date('Y-m-01'),
            'year' => date('Y-01-01'),
            default => date('Y-m-01'),
        };
        
        $orders = SalesOrder::query()
            ->where('order_date', '>=', $startDate)
            ->get();
        
        $stats = [
            'total_orders' => count($orders),
            'total_revenue' => 0,
            'pending_orders' => 0,
            'confirmed_orders' => 0,
            'fulfilled_orders' => 0,
            'cancelled_orders' => 0,
        ];
        
        foreach ($orders as $orderData) {
            $order = SalesOrder::hydrate($orderData);
            $stats['total_revenue'] += $order->total_amount;
            
            $stats["{$order->status}_orders"]++;
        }
        
        return $stats;
    }
    
    /**
     * Generate invoice number
     */
    private function generateInvoiceNumber(): string
    {
        $lastInvoice = Invoice::query()
            ->orderBy('id', 'DESC')
            ->first();
        
        $lastNumber = 0;
        if ($lastInvoice) {
            $invoiceObj = Invoice::hydrate($lastInvoice);
            $lastNumber = (int) substr($invoiceObj->invoice_number, 4);
        }
        
        return 'INV-' . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }
}
