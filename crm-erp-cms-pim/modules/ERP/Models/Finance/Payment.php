<?php

namespace Modules\ERP\Models\Finance;

use Core\ORM\Model;
use App\Models\User;
use Modules\ERP\Models\Sales\Customer;
use Modules\ERP\Models\Procurement\Vendor;

class Payment extends Model
{
    protected string $table = 'erp_payments';
    
    protected array $fillable = [
        'payment_number', 'payment_type', 'customer_id', 'vendor_id',
        'invoice_id', 'payment_date', 'amount', 'payment_method',
        'reference', 'notes', 'created_by'
    ];
    
    protected array $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2'
    ];
    
    /**
     * Get the customer (for receipts)
     */
    public function customer(): ?Customer
    {
        if (!$this->customer_id) {
            return null;
        }
        return Customer::find($this->customer_id);
    }
    
    /**
     * Get the vendor (for payments)
     */
    public function vendor(): ?Vendor
    {
        if (!$this->vendor_id) {
            return null;
        }
        return Vendor::find($this->vendor_id);
    }
    
    /**
     * Get the invoice
     */
    public function invoice(): ?Invoice
    {
        if (!$this->invoice_id) {
            return null;
        }
        return Invoice::find($this->invoice_id);
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
     * Generate payment number
     */
    public static function generatePaymentNumber(string $type = 'receipt'): string
    {
        $prefix = $type === 'receipt' ? 'RCP' : 'PAY';
        $date = date('Ymd');
        $lastPayment = self::where('payment_number', 'LIKE', "{$prefix}{$date}%")
            ->where('payment_type', $type)
            ->orderBy('id', 'DESC')
            ->first();
        
        if ($lastPayment) {
            $lastNumber = (int)substr($lastPayment->payment_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Check if payment is a receipt
     */
    public function isReceipt(): bool
    {
        return $this->payment_type === 'receipt';
    }
    
    /**
     * Check if payment is an expense
     */
    public function isExpense(): bool
    {
        return $this->payment_type === 'payment';
    }
}
