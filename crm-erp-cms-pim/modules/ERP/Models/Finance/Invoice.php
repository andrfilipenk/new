<?php

namespace Modules\ERP\Models\Finance;

use Core\ORM\Model;
use App\Models\User;
use Modules\ERP\Models\Sales\Customer;
use Modules\ERP\Models\Procurement\Vendor;
use Modules\ERP\Models\Sales\SalesOrder;
use Modules\ERP\Models\Procurement\PurchaseOrder;

class Invoice extends Model
{
    protected string $table = 'erp_invoices';
    
    protected array $fillable = [
        'invoice_number', 'invoice_type', 'customer_id', 'vendor_id',
        'sales_order_id', 'purchase_order_id', 'invoice_date', 'due_date',
        'status', 'subtotal', 'tax_amount', 'discount_amount', 'total_amount',
        'paid_amount', 'balance_due', 'notes', 'terms', 'created_by'
    ];
    
    protected array $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2'
    ];
    
    /**
     * Get the customer (for sales invoices)
     */
    public function customer(): ?Customer
    {
        if (!$this->customer_id) {
            return null;
        }
        return Customer::find($this->customer_id);
    }
    
    /**
     * Get the vendor (for purchase invoices)
     */
    public function vendor(): ?Vendor
    {
        if (!$this->vendor_id) {
            return null;
        }
        return Vendor::find($this->vendor_id);
    }
    
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
     * Get payments
     */
    public function payments(): array
    {
        return Payment::where('invoice_id', $this->id)
            ->orderBy('payment_date', 'DESC')
            ->get();
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
     * Generate invoice number
     */
    public static function generateInvoiceNumber(string $type = 'sales'): string
    {
        $prefix = $type === 'sales' ? 'INV' : 'BILL';
        $date = date('Ymd');
        $lastInvoice = self::where('invoice_number', 'LIKE', "{$prefix}{$date}%")
            ->where('invoice_type', $type)
            ->orderBy('id', 'DESC')
            ->first();
        
        if ($lastInvoice) {
            $lastNumber = (int)substr($lastInvoice->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Add payment
     */
    public function addPayment(float $amount): void
    {
        $this->paid_amount += $amount;
        $this->balance_due = $this->total_amount - $this->paid_amount;
        
        // Update status
        if ($this->balance_due <= 0) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        }
        
        $this->save();
    }
    
    /**
     * Check if overdue
     */
    public function isOverdue(): bool
    {
        if (!$this->due_date || $this->status === 'paid') {
            return false;
        }
        return strtotime($this->due_date) < time();
    }
    
    /**
     * Check if fully paid
     */
    public function isFullyPaid(): bool
    {
        return $this->balance_due <= 0;
    }
}
