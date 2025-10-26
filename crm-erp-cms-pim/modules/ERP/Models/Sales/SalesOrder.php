<?php

namespace Modules\ERP\Models\Sales;

use Core\ORM\Model;
use App\Models\User;
use Modules\ERP\Models\Inventory\Warehouse;

class SalesOrder extends Model
{
    protected string $table = 'erp_sales_orders';
    
    protected array $fillable = [
        'so_number', 'customer_id', 'warehouse_id', 'order_date', 'delivery_date',
        'shipped_date', 'status', 'shipping_method', 'shipping_tracking',
        'subtotal', 'tax_amount', 'discount_amount', 'shipping_cost', 'total_amount',
        'notes', 'shipping_address', 'created_by', 'approved_by', 'approved_at'
    ];
    
    protected array $casts = [
        'order_date' => 'date',
        'delivery_date' => 'date',
        'shipped_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime'
    ];
    
    /**
     * Get the customer
     */
    public function customer(): ?Customer
    {
        if (!$this->customer_id) {
            return null;
        }
        return Customer::find($this->customer_id);
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
     * Get sales order items
     */
    public function items(): array
    {
        return SalesOrderItem::where('sales_order_id', $this->id)->get();
    }
    
    /**
     * Get the creator
     */
    public function creator(): ?User
    {
        if (!$this->created_by) {
            return null;
        }
        return User::find($this->created_by);
    }
    
    /**
     * Get the approver
     */
    public function approver(): ?User
    {
        if (!$this->approved_by) {
            return null;
        }
        return User::find($this->approved_by);
    }
    
    /**
     * Calculate totals
     */
    public function calculateTotals(): void
    {
        $items = $this->items();
        $this->subtotal = 0;
        $this->tax_amount = 0;
        
        foreach ($items as $item) {
            $this->subtotal += $item->line_total;
            $this->tax_amount += $item->tax_amount;
        }
        
        $this->total_amount = $this->subtotal + $this->tax_amount - $this->discount_amount + $this->shipping_cost;
        $this->save();
    }
    
    /**
     * Generate SO number
     */
    public static function generateSoNumber(): string
    {
        $prefix = 'SO';
        $date = date('Ymd');
        $lastSO = self::where('so_number', 'LIKE', "{$prefix}{$date}%")
            ->orderBy('id', 'DESC')
            ->first();
        
        if ($lastSO) {
            $lastNumber = (int)substr($lastSO->so_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Check if fully shipped
     */
    public function isFullyShipped(): bool
    {
        foreach ($this->items() as $item) {
            if ($item->shipped_quantity < $item->quantity) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Approve sales order
     */
    public function approve(int $userId): void
    {
        $this->status = 'confirmed';
        $this->approved_by = $userId;
        $this->approved_at = date('Y-m-d H:i:s');
        $this->save();
    }
}
