<?php

namespace Modules\ERP\Models\Inventory;

use Core\ORM\Model;
use App\Models\User;

class Warehouse extends Model
{
    protected string $table = 'erp_warehouses';
    
    protected array $fillable = [
        'code', 'name', 'description', 'address', 'city', 'state', 'country',
        'postal_code', 'phone', 'email', 'manager_id', 'is_active', 'is_default'
    ];
    
    protected array $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean'
    ];
    
    /**
     * Get the warehouse manager
     */
    public function manager(): ?User
    {
        if (!$this->manager_id) {
            return null;
        }
        return User::find($this->manager_id);
    }
    
    /**
     * Get inventory items in this warehouse
     */
    public function inventory(): array
    {
        return Inventory::where('warehouse_id', $this->id)->get();
    }
    
    /**
     * Get stock movements for this warehouse
     */
    public function stockMovements(): array
    {
        return StockMovement::where('warehouse_id', $this->id)
            ->orderBy('movement_date', 'DESC')
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
}
