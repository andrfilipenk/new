<?php

namespace Project\Model;

use Core\Database\Model;

/**
 * Material Model
 * Represents resources consumed in order/position execution
 */
class Material extends Model
{
    protected static $table = 'materials';
    protected static $primaryKey = 'id';

    protected $fillable = [
        'order_id',
        'position_id',
        'material_type',
        'specification',
        'quantity',
        'unit',
        'unit_cost',
        'total_cost',
        'supplier',
        'usage_date',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'usage_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the order this material belongs to
     *
     * @return \Core\Database\Relationship\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the position this material belongs to
     *
     * @return \Core\Database\Relationship\BelongsTo
     */
    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    /**
     * Before save hook to calculate total cost
     *
     * @return void
     */
    protected function beforeSave(): void
    {
        if ($this->quantity && $this->unit_cost) {
            $this->total_cost = $this->quantity * $this->unit_cost;
        }
    }
}
