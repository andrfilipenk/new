<?php

namespace Modules\ERP\Models\Sales;

use Core\ORM\Model;
use Modules\ERP\Models\Inventory\ErpProduct;

class SalesOrderItem extends Model
{
    protected string $table = 'erp_sales_order_items';
    
    protected array $fillable = [
        'sales_order_id', 'product_id', 'quantity', 'shipped_quantity',
        'unit_price', 'tax_rate', 'tax_amount', 'discount_rate',
        'discount_amount', 'line_total', 'notes'
    ];
    
    protected array $casts = [
        'quantity' => 'integer',
        'shipped_quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'line_total' => 'decimal:2'
    ];
    
    /**
     * Get the sales order
     */
    public function salesOrder(): ?SalesOrder
    {
        if (!$this->sales_order_id) {
            return null;
        }
        return SalesOrder::find($this->sales_order_id);
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
        $subtotal = $this->quantity * $this->unit_price;
        
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
     * Get pending quantity to ship
     */
    public function getPendingQuantity(): int
    {
        return $this->quantity - $this->shipped_quantity;
    }
    
    /**
     * Check if fully shipped
     */
    public function isFullyShipped(): bool
    {
        return $this->shipped_quantity >= $this->quantity;
    }
}
