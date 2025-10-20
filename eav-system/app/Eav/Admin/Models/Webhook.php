<?php

namespace Eav\Admin\Models;

use Core\Database\Model;

class Webhook extends Model
{
    protected string $table = 'eav_webhooks';
    protected string $primaryKey = 'webhook_id';
    protected bool $timestamps = true;
    
    protected array $fillable = [
        'webhook_name',
        'target_url',
        'event_types',
        'entity_type_id',
        'secret_key',
        'is_active',
        'max_retries',
        'headers',
        'last_triggered_at'
    ];
    
    protected array $casts = [
        'event_types' => 'json',
        'headers' => 'json',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    protected array $hidden = [
        'secret_key'
    ];
    
    /**
     * Get the entity type for this webhook
     */
    public function entityType()
    {
        return $this->belongsTo(\Eav\Models\EntityType::class, 'entity_type_id', 'entity_type_id');
    }
    
    /**
     * Check if webhook should trigger for event
     */
    public function shouldTrigger(string $eventType): bool
    {
        if (!$this->is_active) {
            return false;
        }
        
        $events = $this->event_types ?? [];
        return in_array($eventType, $events) || in_array('*', $events);
    }
    
    /**
     * Mark webhook as triggered
     */
    public function markAsTriggered(): void
    {
        $this->last_triggered_at = date('Y-m-d H:i:s');
        $this->save();
    }
    
    /**
     * Generate signature for payload
     */
    public function generateSignature(array $payload): string
    {
        if (!$this->secret_key) {
            return '';
        }
        
        return hash_hmac('sha256', json_encode($payload), $this->secret_key);
    }
}
