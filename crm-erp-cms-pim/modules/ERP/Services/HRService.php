<?php

namespace Modules\ERP\Services;

use Modules\ERP\Models\HR\Employee;
use Modules\ERP\Models\HR\Attendance;
use Modules\ERP\Models\HR\Leave;
use Modules\ERP\Models\HR\Payroll;
use App\Services\AuditService;
use App\Services\NotificationService;

/**
 * HR Management Service
 */
class HRService
{
    /**
     * Record attendance
     */
    public function recordAttendance(int $employeeId, string $date, string $checkIn, ?string $checkOut = null, ?int $userId = null): Attendance
    {
        // Check if attendance already exists for this day
        $existing = Attendance::query()
            ->where('employee_id', '=', $employeeId)
            ->where('date', '=', $date)
            ->first();
        
        if ($existing) {
            $attendance = Attendance::hydrate($existing);
            $attendance->check_out = $checkOut;
            $attendance->save();
        } else {
            $attendance = Attendance::create([
                'employee_id' => $employeeId,
                'date' => $date,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'status' => 'present',
            ]);
        }
        
        // Calculate hours worked if check out is provided
        if ($checkOut) {
            $attendance->hours_worked = $this->calculateHours($checkIn, $checkOut);
            $attendance->save();
        }
        
        AuditService::logCreate('Attendance', $attendance->id, $attendance->toArray(), $userId);
        
        return $attendance;
    }
    
    /**
     * Request leave
     */
    public function requestLeave(int $employeeId, array $data, ?int $userId = null): Leave
    {
        $leave = Leave::create(array_merge($data, [
            'employee_id' => $employeeId,
            'status' => 'pending',
        ]));
        
        // Calculate days
        $startDate = new \DateTime($data['start_date']);
        $endDate = new \DateTime($data['end_date']);
        $days = $startDate->diff($endDate)->days + 1;
        
        $leave->days = $days;
        $leave->save();
        
        AuditService::logCreate('Leave', $leave->id, $leave->toArray(), $userId);
        
        // Notify manager
        $employee = Employee::findOrFail($employeeId);
        if ($employee->manager_id) {
            NotificationService::send(
                $employee->manager_id,
                'Leave Request',
                "Employee {$employee->getFullName()} requested {$days} days of leave.",
                'info'
            );
        }
        
        return $leave;
    }
    
    /**
     * Approve leave
     */
    public function approveLeave(int $leaveId, ?int $userId = null): Leave
    {
        $leave = Leave::findOrFail($leaveId);
        
        $leave->status = 'approved';
        $leave->approved_by = $userId;
        $leave->approved_at = date('Y-m-d H:i:s');
        $leave->save();
        
        // Notify employee
        NotificationService::send(
            $leave->employee_id,
            'Leave Approved',
            "Your leave request has been approved.",
            'success'
        );
        
        return $leave;
    }
    
    /**
     * Reject leave
     */
    public function rejectLeave(int $leaveId, string $reason, ?int $userId = null): Leave
    {
        $leave = Leave::findOrFail($leaveId);
        
        $leave->status = 'rejected';
        $leave->rejected_by = $userId;
        $leave->rejected_at = date('Y-m-d H:i:s');
        $leave->rejection_reason = $reason;
        $leave->save();
        
        // Notify employee
        NotificationService::send(
            $leave->employee_id,
            'Leave Rejected',
            "Your leave request has been rejected. Reason: {$reason}",
            'warning'
        );
        
        return $leave;
    }
    
    /**
     * Calculate payroll for employee
     */
    public function calculatePayroll(int $employeeId, string $month, ?int $userId = null): Payroll
    {
        $employee = Employee::findOrFail($employeeId);
        
        // Get attendance for the month
        $startDate = date('Y-m-01', strtotime($month));
        $endDate = date('Y-m-t', strtotime($month));
        
        $attendances = Attendance::query()
            ->where('employee_id', '=', $employeeId)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->get();
        
        $totalHours = 0;
        $presentDays = 0;
        
        foreach ($attendances as $attendanceData) {
            $attendance = Attendance::hydrate($attendanceData);
            if ($attendance->status === 'present') {
                $totalHours += $attendance->hours_worked ?? 0;
                $presentDays++;
            }
        }
        
        // Calculate salary
        $baseSalary = $employee->salary ?? 0;
        $allowances = 0; // Could be calculated from various sources
        $deductions = 0; // Could include taxes, insurance, etc.
        $netSalary = $baseSalary + $allowances - $deductions;
        
        // Create payroll record
        $payroll = Payroll::create([
            'employee_id' => $employeeId,
            'month' => $month,
            'basic_salary' => $baseSalary,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'net_salary' => $netSalary,
            'days_worked' => $presentDays,
            'status' => 'calculated',
        ]);
        
        AuditService::logCreate('Payroll', $payroll->id, $payroll->toArray(), $userId);
        
        return $payroll;
    }
    
    /**
     * Process payroll payment
     */
    public function processPayroll(int $payrollId, ?int $userId = null): Payroll
    {
        $payroll = Payroll::findOrFail($payrollId);
        
        $payroll->status = 'paid';
        $payroll->paid_at = date('Y-m-d H:i:s');
        $payroll->save();
        
        // Notify employee
        NotificationService::send(
            $payroll->employee_id,
            'Salary Processed',
            "Your salary for {$payroll->month} has been processed.",
            'success'
        );
        
        return $payroll;
    }
    
    /**
     * Get employee performance metrics
     */
    public function getEmployeeMetrics(int $employeeId, string $period = 'month'): array
    {
        $startDate = match($period) {
            'today' => date('Y-m-d'),
            'week' => date('Y-m-d', strtotime('-7 days')),
            'month' => date('Y-m-01'),
            'year' => date('Y-01-01'),
            default => date('Y-m-01'),
        };
        
        $endDate = date('Y-m-d');
        
        // Get attendance records
        $attendances = Attendance::query()
            ->where('employee_id', '=', $employeeId)
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->get();
        
        $metrics = [
            'total_days' => count($attendances),
            'present_days' => 0,
            'absent_days' => 0,
            'late_days' => 0,
            'total_hours' => 0,
            'average_hours' => 0,
        ];
        
        foreach ($attendances as $attendanceData) {
            $attendance = Attendance::hydrate($attendanceData);
            
            if ($attendance->status === 'present') {
                $metrics['present_days']++;
                $metrics['total_hours'] += $attendance->hours_worked ?? 0;
            } elseif ($attendance->status === 'absent') {
                $metrics['absent_days']++;
            } elseif ($attendance->status === 'late') {
                $metrics['late_days']++;
                $metrics['total_hours'] += $attendance->hours_worked ?? 0;
            }
        }
        
        if ($metrics['present_days'] > 0) {
            $metrics['average_hours'] = $metrics['total_hours'] / $metrics['present_days'];
        }
        
        // Get leave balance
        $metrics['leave_balance'] = $this->getLeaveBalance($employeeId);
        
        return $metrics;
    }
    
    /**
     * Get leave balance for employee
     */
    public function getLeaveBalance(int $employeeId): array
    {
        $currentYear = date('Y');
        
        // Get approved leaves for current year
        $leaves = Leave::query()
            ->where('employee_id', '=', $employeeId)
            ->where('status', '=', 'approved')
            ->where('start_date', '>=', "{$currentYear}-01-01")
            ->where('start_date', '<=', "{$currentYear}-12-31")
            ->get();
        
        $balance = [
            'total_allocated' => 20, // Default annual leave days
            'used' => 0,
            'remaining' => 20,
        ];
        
        foreach ($leaves as $leaveData) {
            $leave = Leave::hydrate($leaveData);
            $balance['used'] += $leave->days;
        }
        
        $balance['remaining'] = max(0, $balance['total_allocated'] - $balance['used']);
        
        return $balance;
    }
    
    /**
     * Calculate hours worked
     */
    private function calculateHours(string $checkIn, string $checkOut): float
    {
        $start = new \DateTime($checkIn);
        $end = new \DateTime($checkOut);
        
        $diff = $start->diff($end);
        
        return $diff->h + ($diff->i / 60);
    }
    
    /**
     * Get HR dashboard statistics
     */
    public function getHRStatistics(): array
    {
        $totalEmployees = Employee::query()->count();
        $today = date('Y-m-d');
        
        $presentToday = Attendance::query()
            ->where('date', '=', $today)
            ->where('status', '=', 'present')
            ->count();
        
        $pendingLeaves = Leave::query()
            ->where('status', '=', 'pending')
            ->count();
        
        return [
            'total_employees' => $totalEmployees,
            'present_today' => $presentToday,
            'absent_today' => max(0, $totalEmployees - $presentToday),
            'pending_leave_requests' => $pendingLeaves,
        ];
    }
}
