<?php

namespace Project\Model;

use Core\Database\Model;

/**
 * Position Model
 * Represents an item/product line in an order
 */
class Position extends Model
{
    protected static $table = 'positions';
    protected static $primaryKey = 'id';

    protected $fillable = [
        'order_id',
        'position_number',
        'product_code',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
        'status',
        'assigned_to',
        'target_date',
        'specifications',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'target_date' => 'date',
        'specifications' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'Pending';
    const STATUS_IN_PRODUCTION = 'In Production';
    const STATUS_QUALITY_CHECK = 'Quality Check';
    const STATUS_COMPLETED = 'Completed';
    const STATUS_ON_HOLD = 'On Hold';

    /**
     * Get the order this position belongs to
     *
     * @return \Core\Database\Relationship\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get all materials used for this position
     *
     * @return \Core\Database\Relationship\HasMany
     */
    public function materials()
    {
        return $this->hasMany(Material::class, 'position_id');
    }

    /**
     * Get assigned employee
     *
     * @return \Core\Database\Relationship\BelongsTo
     */
    public function assignedEmployee()
    {
        return $this->belongsTo(\User\Model\User::class, 'assigned_to');
    }

    /**
     * Get all available statuses
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_IN_PRODUCTION,
            self::STATUS_QUALITY_CHECK,
            self::STATUS_COMPLETED,
            self::STATUS_ON_HOLD,
        ];
    }

    /**
     * Get status badge class
     *
     * @return string
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_COMPLETED => 'success',
            self::STATUS_IN_PRODUCTION => 'warning',
            self::STATUS_QUALITY_CHECK => 'info',
            self::STATUS_PENDING => 'secondary',
            self::STATUS_ON_HOLD => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Calculate total material cost for this position
     *
     * @return float
     */
    public function getTotalMaterialCost(): float
    {
        return (float) $this->materials()->sum('total_cost');
    }

    /**
     * Before save hook to calculate total price
     *
     * @return void
     */
    protected function beforeSave(): void
    {
        if ($this->quantity && $this->unit_price) {
            $this->total_price = $this->quantity * $this->unit_price;
        }
    }
}
