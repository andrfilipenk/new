<?php

namespace Eav\Admin\Models;

use Core\Database\Model;

class Report extends Model
{
    protected string $table = 'eav_reports';
    protected string $primaryKey = 'report_id';
    protected bool $timestamps = true;
    
    protected array $fillable = [
        'report_name',
        'report_type',
        'entity_type_id',
        'configuration',
        'created_by',
        'is_scheduled',
        'schedule_config',
        'last_run_at'
    ];
    
    protected array $casts = [
        'configuration' => 'json',
        'schedule_config' => 'json',
        'is_scheduled' => 'boolean',
        'last_run_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    const TYPE_SUMMARY = 'summary';
    const TYPE_ANALYTICAL = 'analytical';
    const TYPE_CUSTOM = 'custom';
    
    /**
     * Get the entity type for this report
     */
    public function entityType()
    {
        return $this->belongsTo(\Eav\Models\EntityType::class, 'entity_type_id', 'entity_type_id');
    }
    
    /**
     * Get the user who created this report
     */
    public function creator()
    {
        return $this->belongsTo(\User\Model\User::class, 'created_by', 'id');
    }
    
    /**
     * Update last run timestamp
     */
    public function markAsRun(): void
    {
        $this->last_run_at = date('Y-m-d H:i:s');
        $this->save();
    }
    
    /**
     * Check if report should run now (based on schedule)
     */
    public function shouldRun(): bool
    {
        if (!$this->is_scheduled || !$this->schedule_config) {
            return false;
        }
        
        $schedule = $this->schedule_config;
        $frequency = $schedule['frequency'] ?? 'daily';
        
        if (!$this->last_run_at) {
            return true;
        }
        
        $lastRun = strtotime($this->last_run_at);
        $now = time();
        
        switch ($frequency) {
            case 'hourly':
                return ($now - $lastRun) >= 3600;
            case 'daily':
                return ($now - $lastRun) >= 86400;
            case 'weekly':
                return ($now - $lastRun) >= 604800;
            case 'monthly':
                return ($now - $lastRun) >= 2592000;
            default:
                return false;
        }
    }
}
