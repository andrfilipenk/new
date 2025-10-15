<?php

namespace Project\Model;

use Core\Database\Model;

/**
 * EmployeeActivity Model
 * Represents time tracking and task assignments for employees
 */
class EmployeeActivity extends Model
{
    protected static $table = 'employee_activities';
    protected static $primaryKey = 'id';

    protected $fillable = [
        'project_id',
        'order_id',
        'position_id',
        'employee_id',
        'activity_type',
        'hours',
        'activity_date',
        'description',
        'notes',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'activity_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Activity type constants
    const TYPE_PLANNING = 'Planning';
    const TYPE_EXECUTION = 'Execution';
    const TYPE_QUALITY_CHECK = 'Quality Check';
    const TYPE_DOCUMENTATION = 'Documentation';
    const TYPE_MEETING = 'Meeting';
    const TYPE_OTHER = 'Other';

    /**
     * Get the project this activity belongs to
     *
     * @return \Core\Database\Relationship\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the order this activity belongs to
     *
     * @return \Core\Database\Relationship\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the position this activity belongs to
     *
     * @return \Core\Database\Relationship\BelongsTo
     */
    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }

    /**
     * Get the employee who performed this activity
     *
     * @return \Core\Database\Relationship\BelongsTo
     */
    public function employee()
    {
        return $this->belongsTo(\User\Model\User::class, 'employee_id');
    }

    /**
     * Get all available activity types
     *
     * @return array
     */
    public static function getActivityTypes(): array
    {
        return [
            self::TYPE_PLANNING,
            self::TYPE_EXECUTION,
            self::TYPE_QUALITY_CHECK,
            self::TYPE_DOCUMENTATION,
            self::TYPE_MEETING,
            self::TYPE_OTHER,
        ];
    }

    /**
     * Get activity type badge class
     *
     * @return string
     */
    public function getTypeBadgeClass(): string
    {
        return match($this->activity_type) {
            self::TYPE_EXECUTION => 'primary',
            self::TYPE_QUALITY_CHECK => 'success',
            self::TYPE_PLANNING => 'info',
            self::TYPE_DOCUMENTATION => 'warning',
            self::TYPE_MEETING => 'secondary',
            default => 'secondary',
        };
    }
}
