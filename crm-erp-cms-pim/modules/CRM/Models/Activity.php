<?php

namespace Modules\CRM\Models;

use Core\ORM\Model;
use App\Models\User;

class Activity extends Model
{
    protected string $table = 'crm_activities';
    
    protected array $fillable = [
        'type', 'subject', 'description', 'due_date', 'status',
        'priority', 'related_to_type', 'related_to_id',
        'assigned_to_id', 'created_by_id'
    ];
    
    protected array $casts = [
        'due_date' => 'datetime',
    ];
    
    protected bool $timestamps = true;
    
    public function assignedTo(): ?User
    {
        return User::find($this->assigned_to_id);
    }
    
    public function createdBy(): ?User
    {
        return User::find($this->created_by_id);
    }
    
    public function relatedTo()
    {
        $class = match($this->related_to_type) {
            'account' => Account::class,
            'contact' => Contact::class,
            'lead' => Lead::class,
            'opportunity' => Opportunity::class,
            default => null,
        };
        
        return $class ? $class::find($this->related_to_id) : null;
    }
    
    public function markCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }
    
    public function isOverdue(): bool
    {
        return $this->status === 'planned' && 
               $this->due_date && 
               strtotime($this->due_date) < time();
    }
}
