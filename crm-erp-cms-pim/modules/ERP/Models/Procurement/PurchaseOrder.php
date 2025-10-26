<?php

namespace Modules\ERP\Models\Procurement;

use Core\ORM\Model;
use App\Models\User;
use Modules\ERP\Models\Inventory\Warehouse;

class PurchaseOrder extends Model
{
    protected string $table = 'erp_purchase_orders';
    
    protected array $fillable = [
        'po_number', 'vendor_id', 'warehouse_id', 'order_date', 'expected_date',
        'received_date', 'status', 'subtotal', 'tax_amount', 'discount_amount',
        'shipping_cost', 'total_amount', 'notes', 'payment_terms', 'created_by',
        'approved_by', 'approved_at'
    ];
    
    protected array $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'received_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime'
    ];
    
    /**
     * Get the vendor
     */
    public function vendor(): ?Vendor
    {
        if (!$this->vendor_id) {
            return null;
        }
        return Vendor::find($this->vendor_id);
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
     * Get purchase order items
     */
    public function items(): array
    {
        return PurchaseOrderItem::where('purchase_order_id', $this->id)->get();
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
     * Generate PO number
     */
    public static function generatePoNumber(): string
    {
        $prefix = 'PO';
        $date = date('Ymd');
        $lastPO = self::where('po_number', 'LIKE', "{$prefix}{$date}%")
            ->orderBy('id', 'DESC')
            ->first();
        
        if ($lastPO) {
            $lastNumber = (int)substr($lastPO->po_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Check if fully received
     */
    public function isFullyReceived(): bool
    {
        foreach ($this->items() as $item) {
            if ($item->received_quantity < $item->quantity) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Approve purchase order
     */
    public function approve(int $userId): void
    {
        $this->status = 'sent';
        $this->approved_by = $userId;
        $this->approved_at = date('Y-m-d H:i:s');
        $this->save();
    }
}
