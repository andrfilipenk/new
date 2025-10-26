<?php

namespace Modules\ERP\Models\Sales;

use Core\ORM\Model;

class Customer extends Model
{
    protected string $table = 'erp_customers';
    
    protected array $fillable = [
        'code', 'name', 'contact_person', 'email', 'phone', 'mobile', 'website',
        'billing_address', 'billing_city', 'billing_state', 'billing_country', 'billing_postal_code',
        'shipping_address', 'shipping_city', 'shipping_state', 'shipping_country', 'shipping_postal_code',
        'tax_number', 'payment_terms', 'credit_limit', 'outstanding_balance', 'notes', 'is_active'
    ];
    
    protected array $casts = [
        'credit_limit' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'is_active' => 'boolean'
    ];
    
    /**
     * Get sales orders for this customer
     */
    public function salesOrders(): array
    {
        return SalesOrder::where('customer_id', $this->id)
            ->orderBy('order_date', 'DESC')
            ->get();
    }
    
    /**
     * Get billing address
     */
    public function getBillingAddress(): string
    {
        $parts = array_filter([
            $this->billing_address,
            $this->billing_city,
            $this->billing_state,
            $this->billing_postal_code,
            $this->billing_country
        ]);
        return implode(', ', $parts);
    }
    
    /**
     * Get shipping address
     */
    public function getShippingAddress(): string
    {
        $parts = array_filter([
            $this->shipping_address ?: $this->billing_address,
            $this->shipping_city ?: $this->billing_city,
            $this->shipping_state ?: $this->billing_state,
            $this->shipping_postal_code ?: $this->billing_postal_code,
            $this->shipping_country ?: $this->billing_country
        ]);
        return implode(', ', $parts);
    }
    
    /**
     * Check if customer has exceeded credit limit
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
