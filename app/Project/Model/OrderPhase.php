<?php

namespace Project\Model;

use Core\Database\Model;

/**
 * OrderPhase Model
 * Represents a time-bounded stage in order execution
 */
class OrderPhase extends Model
{
    protected static $table = 'order_phases';
    protected static $primaryKey = 'id';

    protected $fillable = [
        'order_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
        'completion_percentage',
        'sequence_order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'completion_percentage' => 'integer',
        'sequence_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'Pending';
    const STATUS_ACTIVE = 'Active';
    const STATUS_COMPLETED = 'Completed';
    const STATUS_DELAYED = 'Delayed';

    /**
     * Get the order this phase belongs to
     *
     * @return \Core\Database\Relationship\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
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
            self::STATUS_ACTIVE,
            self::STATUS_COMPLETED,
            self::STATUS_DELAYED,
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
            self::STATUS_ACTIVE => 'primary',
            self::STATUS_PENDING => 'secondary',
            self::STATUS_DELAYED => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Check if phase is delayed
     *
     * @return bool
     */
    public function isDelayed(): bool
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return false;
        }

        $now = new \DateTime();
        $endDate = new \DateTime($this->end_date);

        return $now > $endDate;
    }

    /**
     * Get phase duration in days
     *
     * @return int
     */
    public function getDurationDays(): int
    {
        $start = new \DateTime($this->start_date);
        $end = new \DateTime($this->end_date);
        
        return (int) $start->diff($end)->days;
    }
}
