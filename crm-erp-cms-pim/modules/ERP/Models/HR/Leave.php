<?php

namespace Modules\ERP\Models\HR;

use Core\ORM\Model;
use App\Models\User;

class Leave extends Model
{
    protected string $table = 'erp_leaves';
    
    protected array $fillable = [
        'employee_id', 'leave_type_id', 'start_date', 'end_date',
        'days_requested', 'status', 'reason', 'rejection_reason',
        'approved_by', 'approved_at'
    ];
    
    protected array $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days_requested' => 'integer',
        'approved_at' => 'datetime'
    ];
    
    /**
     * Get the employee
     */
    public function employee(): ?Employee
    {
        if (!$this->employee_id) {
            return null;
        }
        return Employee::find($this->employee_id);
    }
    
    /**
     * Get the leave type
     */
    public function leaveType(): ?LeaveType
    {
        if (!$this->leave_type_id) {
            return null;
        }
        return LeaveType::find($this->leave_type_id);
    }
    
    /**
     * Get the approver
     */
    public function approver(): ?User
    {
        if (!$this->approved_by) {
            return null;
        }
        return User::find($this->approved_by);
    }
    
    /**
     * Calculate days requested
     */
    public function calculateDays(): void
    {
        if (!$this->start_date || !$this->end_date) {
            $this->days_requested = 0;
            $this->save();
            return;
        }
        
        $start = strtotime($this->start_date);
        $end = strtotime($this->end_date);
        $days = ($end - $start) / (24 * 60 * 60) + 1;
        
        $this->days_requested = (int)$days;
        $this->save();
    }
    
    /**
     * Approve leave
     */
    public function approve(int $userId): void
    {
        $this->status = 'approved';
        $this->approved_by = $userId;
        $this->approved_at = date('Y-m-d H:i:s');
        $this->save();
    }
    
    /**
     * Reject leave
     */
    public function reject(int $userId, string $reason = ''): void
    {
        $this->status = 'rejected';
        $this->approved_by = $userId;
        $this->rejection_reason = $reason;
        $this->approved_at = date('Y-m-d H:i:s');
        $this->save();
    }
    
    /**
     * Check if leave is approved
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
    
    /**
     * Check if leave is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
