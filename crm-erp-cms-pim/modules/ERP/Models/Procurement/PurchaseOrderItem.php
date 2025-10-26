<?php

namespace Modules\ERP\Models\Procurement;

use Core\ORM\Model;
use Modules\ERP\Models\Inventory\ErpProduct;

class PurchaseOrderItem extends Model
{
    protected string $table = 'erp_purchase_order_items';
    
    protected array $fillable = [
        'purchase_order_id', 'product_id', 'quantity', 'received_quantity',
        'unit_cost', 'tax_rate', 'tax_amount', 'discount_rate',
        'discount_amount', 'line_total', 'notes'
    ];
    
    protected array $casts = [
        'quantity' => 'integer',
        'received_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'line_total' => 'decimal:2'
    ];
    
    /**
     * Get the purchase order
     */
    public function purchaseOrder(): ?PurchaseOrder
    {
        if (!$this->purchase_order_id) {
            return null;
        }
        return PurchaseOrder::find($this->purchase_order_id);
    }
    
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
     * Calculate line total
     */
    public function calculateLineTotal(): void
    {
        $subtotal = $this->quantity * $this->unit_cost;
        
        // Apply discount
        if ($this->discount_rate > 0) {
            $this->discount_amount = $subtotal * ($this->discount_rate / 100);
        }
        $subtotal -= $this->discount_amount;
        
        // Calculate tax
        if ($this->tax_rate > 0) {
            $this->tax_amount = $subtotal * ($this->tax_rate / 100);
        }
        
        $this->line_total = $subtotal + $this->tax_amount;
        $this->save();
    }
    
    /**
     * Get pending quantity
     */
    public function getPendingQuantity(): int
    {
        return $this->quantity - $this->received_quantity;
    }
    
    /**
     * Check if fully received
     */
    public function isFullyReceived(): bool
    {
        return $this->received_quantity >= $this->quantity;
    }
}
