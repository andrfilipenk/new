<?php

namespace Eav\Admin\Models;

use Core\Database\Model;

class ExportJob extends Model
{
    protected string $table = 'eav_export_jobs';
    protected string $primaryKey = 'job_id';
    protected bool $timestamps = false;
    
    protected array $fillable = [
        'entity_type_id',
        'user_id',
        'export_name',
        'file_path',
        'format',
        'filter_config',
        'column_config',
        'status',
        'total_rows',
        'started_at',
        'completed_at'
    ];
    
    protected array $casts = [
        'filter_config' => 'json',
        'column_config' => 'json',
        'created_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];
    
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    
    const FORMAT_CSV = 'csv';
    const FORMAT_XLSX = 'xlsx';
    const FORMAT_JSON = 'json';
    const FORMAT_XML = 'xml';
    
    /**
     * Get the entity type for this export
     */
    public function entityType()
    {
        return $this->belongsTo(\Eav\Models\EntityType::class, 'entity_type_id', 'entity_type_id');
    }
    
    /**
     * Get the user who created this export
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
    public function markAsCompleted(string $filePath, int $totalRows): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->file_path = $filePath;
        $this->total_rows = $totalRows;
        $this->completed_at = date('Y-m-d H:i:s');
        $this->save();
    }
    
    /**
     * Mark job as failed
     */
    public function markAsFailed(): void
    {
        $this->status = self::STATUS_FAILED;
        $this->completed_at = date('Y-m-d H:i:s');
        $this->save();
    }
    
    /**
     * Check if job is complete
     */
    public function isComplete(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED]);
    }
    
    /**
     * Get file download URL
     */
    public function getDownloadUrl(): ?string
    {
        if (!$this->file_path || $this->status !== self::STATUS_COMPLETED) {
            return null;
        }
        
        return '/eav/admin/exports/download/' . $this->job_id;
    }
}
