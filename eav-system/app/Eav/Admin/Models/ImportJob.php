<?php

namespace Eav\Admin\Models;

use Core\Database\Model;

class ImportJob extends Model
{
    protected string $table = 'eav_import_jobs';
    protected string $primaryKey = 'job_id';
    protected bool $timestamps = false;
    
    protected array $fillable = [
        'entity_type_id',
        'user_id',
        'file_name',
        'file_path',
        'status',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'error_details',
        'started_at',
        'completed_at'
    ];
    
    protected array $casts = [
        'error_details' => 'json',
        'created_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];
    
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    
    /**
     * Get the entity type for this import
     */
    public function entityType()
    {
        return $this->belongsTo(\Eav\Models\EntityType::class, 'entity_type_id', 'entity_type_id');
    }
    
    /**
     * Get the user who created this import
     */
    public function user()
    {
        return $this->belongsTo(\User\Model\User::class, 'user_id', 'id');
    }
    
    /**
     * Mark job as started
     */
    public function markAsStarted(): void
    {
        $this->status = self::STATUS_PROCESSING;
        $this->started_at = date('Y-m-d H:i:s');
        $this->save();
    }
    
    /**
     * Mark job as completed
     */
    public function markAsCompleted(): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = date('Y-m-d H:i:s');
        $this->save();
    }
    
    /**
     * Mark job as failed
     */
    public function markAsFailed(array $errors): void
    {
        $this->status = self::STATUS_FAILED;
        $this->error_details = $errors;
        $this->completed_at = date('Y-m-d H:i:s');
        $this->save();
    }
    
    /**
     * Update progress
     */
    public function updateProgress(int $processed, int $successful, int $failed): void
    {
        $this->processed_rows = $processed;
        $this->successful_rows = $successful;
        $this->failed_rows = $failed;
        $this->save();
    }
    
    /**
     * Get success rate
     */
    public function getSuccessRate(): float
    {
        if ($this->total_rows == 0) {
            return 0;
        }
        
        return ($this->successful_rows / $this->total_rows) * 100;
    }
    
    /**
     * Check if job is complete
     */
    public function isComplete(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED]);
    }
    
    /**
     * Get execution time in seconds
     */
    public function getExecutionTime(): ?int
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }
        
        return strtotime($this->completed_at) - strtotime($this->started_at);
    }
}
