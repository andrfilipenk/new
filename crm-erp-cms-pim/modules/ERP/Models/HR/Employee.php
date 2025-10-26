<?php

namespace Modules\ERP\Models\HR;

use Core\ORM\Model;
use App\Models\User;

class Employee extends Model
{
    protected string $table = 'erp_employees';
    
    protected array $fillable = [
        'employee_code', 'user_id', 'first_name', 'last_name', 'email', 'phone',
        'mobile', 'date_of_birth', 'gender', 'address', 'city', 'state', 'country',
        'postal_code', 'department', 'position', 'hire_date', 'termination_date',
        'employment_type', 'status', 'salary', 'bank_account', 'tax_id', 'manager_id', 'notes'
    ];
    
    protected array $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'salary' => 'decimal:2'
    ];
    
    /**
     * Get the linked user account
     */
    public function user(): ?User
    {
        if (!$this->user_id) {
            return null;
        }
        return User::find($this->user_id);
    }
    
    /**
     * Get the manager
     */
    public function manager(): ?self
    {
        if (!$this->manager_id) {
            return null;
        }
        return self::find($this->manager_id);
    }
    
    /**
     * Get subordinates
     */
    public function subordinates(): array
    {
        return self::where('manager_id', $this->id)->get();
    }
    
    /**
     * Get full name
     */
    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }
    
    /**
     * Get full address
     */
    public function getFullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country
        ]);
        return implode(', ', $parts);
    }
    
    /**
     * Get attendance records
     */
    public function attendanceRecords(): array
    {
        return Attendance::where('employee_id', $this->id)
            ->orderBy('attendance_date', 'DESC')
            ->get();
    }
    
    /**
     * Get leave requests
     */
    public function leaveRequests(): array
    {
        return Leave::where('employee_id', $this->id)
            ->orderBy('start_date', 'DESC')
            ->get();
    }
    
    /**
     * Get payroll records
     */
    public function payrolls(): array
    {
        return Payroll::where('employee_id', $this->id)
            ->orderBy('payroll_period', 'DESC')
            ->get();
    }
    
    /**
     * Check if employee is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
    
    /**
     * Calculate years of service
     */
    public function getYearsOfService(): float
    {
        if (!$this->hire_date) {
            return 0;
        }
        
        $endDate = $this->termination_date ?: date('Y-m-d');
        $diff = strtotime($endDate) - strtotime($this->hire_date);
        return round($diff / (365 * 24 * 60 * 60), 1);
    }
}
