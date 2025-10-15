<?php

namespace Project\Model;

use Core\Database\Model;

/**
 * Project Model
 * Represents a business project containing orders, tasks, and resources
 */
class Project extends Model
{
    protected static $table = 'projects';
    protected static $primaryKey = 'id';

    protected $fillable = [
        'name',
        'code',
        'description',
        'client_id',
        'start_date',
        'end_date',
        'status',
        'budget',
        'priority',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PLANNING = 'Planning';
    const STATUS_ACTIVE = 'Active';
    const STATUS_ON_HOLD = 'On Hold';
    const STATUS_COMPLETED = 'Completed';
    const STATUS_CANCELLED = 'Cancelled';

    // Priority constants
    const PRIORITY_LOW = 'Low';
    const PRIORITY_MEDIUM = 'Medium';
    const PRIORITY_HIGH = 'High';
    const PRIORITY_CRITICAL = 'Critical';

    /**
     * Get all orders for this project
     *
     * @return \Core\Database\Relationship\HasMany
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'project_id');
    }

    /**
     * Get all employee activities for this project
     *
     * @return \Core\Database\Relationship\HasMany
     */
    public function activities()
    {
        return $this->hasMany(EmployeeActivity::class, 'project_id');
    }

    /**
     * Get client information
     *
     * @return \Core\Database\Relationship\BelongsTo
     */
    public function client()
    {
        return $this->belongsTo(\User\Model\User::class, 'client_id');
    }

    /**
     * Get creator user
     *
     * @return \Core\Database\Relationship\BelongsTo
     */
    public function creator()
    {
        return $this->belongsTo(\User\Model\User::class, 'created_by');
    }

    /**
     * Get all available statuses
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PLANNING,
            self::STATUS_ACTIVE,
            self::STATUS_ON_HOLD,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Get all available priorities
     *
     * @return array
     */
    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW,
            self::PRIORITY_MEDIUM,
            self::PRIORITY_HIGH,
            self::PRIORITY_CRITICAL,
        ];
    }

    /**
     * Check if project is active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if project is completed
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
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
            self::STATUS_ACTIVE => 'warning',
            self::STATUS_PLANNING => 'info',
            self::STATUS_ON_HOLD => 'secondary',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary',
        };
    }
}
