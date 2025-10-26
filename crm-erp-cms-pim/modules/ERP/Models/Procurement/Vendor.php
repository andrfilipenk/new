<?php

namespace Modules\ERP\Models\Procurement;

use Core\ORM\Model;

class Vendor extends Model
{
    protected string $table = 'erp_vendors';
    
    protected array $fillable = [
        'code', 'name', 'contact_person', 'email', 'phone', 'mobile', 'website',
        'address', 'city', 'state', 'country', 'postal_code', 'tax_number',
        'payment_terms', 'credit_limit', 'outstanding_balance', 'notes', 'is_active'
    ];
    
    protected array $casts = [
        'credit_limit' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'is_active' => 'boolean'
    ];
    
    /**
     * Get purchase orders for this vendor
     */
    public function purchaseOrders(): array
    {
        return PurchaseOrder::where('vendor_id', $this->id)
            ->orderBy('order_date', 'DESC')
            ->get();
    }
    
    /**
     * Get full address
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country
        ]);
        return implode(', ', $parts);
    }
    
    /**
     * Check if vendor has exceeded credit limit
     */
    public function hasExceededCreditLimit(): bool
    {
        return $this->credit_limit > 0 && $this->outstanding_balance >= $this->credit_limit;
    }
    
    /**
     * Get available credit
     */
    public function getAvailableCredit(): float
    {
        if ($this->credit_limit <= 0) {
            return 0;
        }
        return max(0, $this->credit_limit - $this->outstanding_balance);
    }
}
