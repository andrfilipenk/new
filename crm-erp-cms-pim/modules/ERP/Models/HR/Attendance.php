<?php

namespace Modules\ERP\Models\HR;

use Core\ORM\Model;
use App\Models\User;

class Attendance extends Model
{
    protected string $table = 'erp_attendance';
    
    protected array $fillable = [
        'employee_id', 'attendance_date', 'check_in', 'check_out',
        'work_hours', 'overtime_hours', 'status', 'notes', 'approved_by'
    ];
    
    protected array $casts = [
        'attendance_date' => 'date',
        'work_hours' => 'integer',
        'overtime_hours' => 'integer'
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
     * Calculate work hours
     */
    public function calculateWorkHours(): void
    {
        if (!$this->check_in || !$this->check_out) {
            $this->work_hours = 0;
            $this->save();
            return;
        }
        
        $checkIn = strtotime($this->check_in);
        $checkOut = strtotime($this->check_out);
        $diffMinutes = ($checkOut - $checkIn) / 60;
        
        // Standard work day is 8 hours
        $standardMinutes = 8 * 60;
        
        if ($diffMinutes <= $standardMinutes) {
            $this->work_hours = (int)$diffMinutes;
            $this->overtime_hours = 0;
        } else {
            $this->work_hours = $standardMinutes;
            $this->overtime_hours = (int)($diffMinutes - $standardMinutes);
        }
        
        $this->save();
    }
    
    /**
     * Get work hours in decimal format
     */
    public function getWorkHoursDecimal(): float
    {
        return round($this->work_hours / 60, 2);
    }
    
    /**
     * Get overtime hours in decimal format
     */
    public function getOvertimeHoursDecimal(): float
    {
        return round($this->overtime_hours / 60, 2);
    }
}
