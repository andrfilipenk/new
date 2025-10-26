<?php

namespace Modules\ERP\Models\Inventory;

use Core\ORM\Model;
use App\Models\User;

class StockMovement extends Model
{
    protected string $table = 'erp_stock_movements';
    
    protected array $fillable = [
        'reference_no', 'product_id', 'warehouse_id', 'type', 'quantity',
        'quantity_before', 'quantity_after', 'cost', 'reference_type',
        'reference_id', 'notes', 'created_by', 'movement_date'
    ];
    
    protected array $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'cost' => 'decimal:2',
        'movement_date' => 'datetime'
    ];
    
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
     * Generate reference number
     */
    public static function generateReferenceNo(): string
    {
        $prefix = 'SM';
        $date = date('Ymd');
        $lastMovement = self::where('reference_no', 'LIKE', "{$prefix}{$date}%")
            ->orderBy('id', 'DESC')
            ->first();
        
        if ($lastMovement) {
            $lastNumber = (int)substr($lastMovement->reference_no, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Check if movement is inbound
     */
    public function isInbound(): bool
    {
        return in_array($this->type, ['in', 'adjustment']) && $this->quantity > 0;
    }
    
    /**
     * Check if movement is outbound
     */
    public function isOutbound(): bool
    {
        return in_array($this->type, ['out', 'adjustment']) && $this->quantity < 0;
    }
}
