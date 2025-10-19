<?php

namespace Eav\Admin\Models;

use Core\Database\Model;

class AuditLog extends Model
{
    protected string $table = 'eav_audit_log';
    protected string $primaryKey = 'log_id';
    protected bool $timestamps = false;
    
    protected array $fillable = [
        'event_type',
        'entity_type',
        'entity_id',
        'user_id',
        'ip_address',
        'user_agent',
        'request_data',
        'response_status',
        'execution_time'
    ];
    
    protected array $casts = [
        'request_data' => 'json',
        'created_at' => 'datetime'
    ];
    
    /**
     * Get the user who performed the action
     */
    public function user()
    {
        return $this->belongsTo(\User\Model\User::class, 'user_id', 'id');
    }
    
    /**
     * Scope to filter by event type
     */
    public function scopeEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }
    
    /**
     * Scope to filter by entity type
     */
    public function scopeEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }
    
    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
    
    /**
     * Scope to filter by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
    
    /**
     * Scope to filter by HTTP status
     */
    public function scopeStatus($query, int $status)
    {
        return $query->where('response_status', $status);
    }
    
    /**
     * Scope to get failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('response_status', '>=', 400);
    }
}
