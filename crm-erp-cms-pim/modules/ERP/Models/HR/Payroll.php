<?php

namespace Modules\ERP\Models\HR;

use Core\ORM\Model;
use App\Models\User;

class Payroll extends Model
{
    protected string $table = 'erp_payroll';
    
    protected array $fillable = [
        'employee_id', 'payroll_period', 'pay_date', 'basic_salary',
        'allowances', 'overtime_pay', 'bonuses', 'gross_salary',
        'tax_deduction', 'insurance_deduction', 'other_deductions',
        'total_deductions', 'net_salary', 'status', 'notes',
        'processed_by', 'processed_at'
    ];
    
    protected array $casts = [
        'pay_date' => 'date',
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'bonuses' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'tax_deduction' => 'decimal:2',
        'insurance_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'processed_at' => 'datetime'
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
     * Get the processor
     */
    public function processor(): ?User
    {
        if (!$this->processed_by) {
            return null;
        }
        return User::find($this->processed_by);
    }
    
    /**
     * Calculate gross salary
     */
    public function calculateGrossSalary(): void
    {
        $this->gross_salary = $this->basic_salary + $this->allowances + 
                             $this->overtime_pay + $this->bonuses;
        $this->save();
    }
    
    /**
     * Calculate total deductions
     */
    public function calculateTotalDeductions(): void
    {
        $this->total_deductions = $this->tax_deduction + $this->insurance_deduction + 
                                  $this->other_deductions;
        $this->save();
    }
    
    /**
     * Calculate net salary
     */
    public function calculateNetSalary(): void
    {
        $this->calculateGrossSalary();
        $this->calculateTotalDeductions();
        $this->net_salary = $this->gross_salary - $this->total_deductions;
        $this->save();
    }
    
    /**
     * Process payroll
     */
    public function process(int $userId): void
    {
        $this->calculateNetSalary();
        $this->status = 'processed';
        $this->processed_by = $userId;
        $this->processed_at = date('Y-m-d H:i:s');
        $this->save();
    }
    
    /**
     * Mark as paid
     */
    public function markAsPaid(): void
    {
        $this->status = 'paid';
        $this->save();
    }
    
    /**
     * Check if payroll is processed
     */
    public function isProcessed(): bool
    {
        return $this->status === 'processed' || $this->status === 'paid';
    }
}
