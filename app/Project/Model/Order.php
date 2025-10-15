<?php

namespace Project\Model;

use Core\Database\Model;

/**
 * Order Model
 * Represents a work order within a project
 */
class Order extends Model
{
    protected static $table = 'orders';
    protected static $primaryKey = 'id';

    protected $fillable = [
        'project_id',
        'order_number',
        'title',
        'description',
        'start_date',
        'end_date',
        'status',
        'total_value',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_value' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_DRAFT = 'Draft';
    const STATUS_CONFIRMED = 'Confirmed';
    const STATUS_IN_PROGRESS = 'In Progress';
    const STATUS_READY = 'Ready';
    const STATUS_DELIVERED = 'Delivered';
    const STATUS_CLOSED = 'Closed';

    /**
     * Get the project this order belongs to
     *
     * @return \Core\Database\Relationship\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get all positions for this order
     *
     * @return \Core\Database\Relationship\HasMany
     */
    public function positions()
    {
        return $this->hasMany(Position::class, 'order_id');
    }

    /**
     * Get all phases for this order
     *
     * @return \Core\Database\Relationship\HasMany
     */
    public function phases()
    {
        return $this->hasMany(OrderPhase::class, 'order_id');
    }

    /**
     * Get all materials used in this order
     *
     * @return \Core\Database\Relationship\HasMany
     */
    public function materials()
    {
        return $this->hasMany(Material::class, 'order_id');
    }

    /**
     * Get all employee activities for this order
     *
     * @return \Core\Database\Relationship\HasMany
     */
    public function activities()
    {
        return $this->hasMany(EmployeeActivity::class, 'order_id');
    }

    /**
     * Get all comments for this order
     *
     * @return \Core\Database\Relationship\MorphMany
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Get all available statuses
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_CONFIRMED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_READY,
            self::STATUS_DELIVERED,
            self::STATUS_CLOSED,
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
            self::STATUS_DELIVERED, self::STATUS_CLOSED => 'success',
            self::STATUS_IN_PROGRESS => 'warning',
            self::STATUS_READY => 'info',
            self::STATUS_CONFIRMED => 'primary',
            self::STATUS_DRAFT => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Calculate order progress percentage
     *
     * @return float
     */
    public function getProgressPercentage(): float
    {
        $totalPositions = $this->positions()->count();
        if ($totalPositions === 0) {
            return 0.0;
        }

        $completedPositions = $this->positions()
            ->where('status', Position::STATUS_COMPLETED)
            ->count();

        return round(($completedPositions / $totalPositions) * 100, 2);
    }
}
