<?php

namespace Modules\ERP\Controllers;

use Core\Request;
use Core\Response;
use Modules\ERP\Models\HR\Employee;
use Modules\ERP\Models\HR\Payroll;
use Core\Validation\Validator;
use App\Services\AuditService;

/**
 * Payroll Controller
 * 
 * Handles payroll processing and salary management
 */
class PayrollController
{
    /**
     * Display list of payroll records
     */
    public function index(Request $request): Response
    {
        $month = $request->get('month');
        $year = $request->get('year');
        
        $query = \Core\Database\DB::table('erp_payroll');
        
        if ($month) {
            $query->where('pay_period_month', '=', $month);
        }
        
        if ($year) {
            $query->where('pay_period_year', '=', $year);
        }
        
        $payrolls = $query->orderBy('pay_date', 'DESC')->get();
        $payrolls = array_map(fn($row) => Payroll::hydrate($row), $payrolls);
        
        if ($request->expectsJson()) {
            return Response::json(['payrolls' => $payrolls]);
        }
        
        return view('erp/payroll/index', [
            'payrolls' => $payrolls,
            'month' => $month,
            'year' => $year,
        ]);
    }
    
    /**
     * Generate payroll for employees
     */
    public function generate(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer',
            'pay_date' => 'required|date',
            'employee_ids' => 'required',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $data = $validator->validated();
            $employeeIds = json_decode($data['employee_ids'], true);
            $generated = [];
            
            foreach ($employeeIds as $employeeId) {
                $employee = Employee::findOrFail($employeeId);
                
                // Check if payroll already exists
                $existing = Payroll::where('employee_id', $employeeId)
                    ->where('pay_period_month', $data['month'])
                    ->where('pay_period_year', $data['year'])
                    ->first();
                
                if ($existing) {
                    continue; // Skip if already generated
                }
                
                $baseSalary = $employee['salary'] ?? 0;
                
                // Calculate deductions (simplified - 10% tax, 5% insurance)
                $taxDeduction = $baseSalary * 0.10;
                $insuranceDeduction = $baseSalary * 0.05;
                $totalDeductions = $taxDeduction + $insuranceDeduction;
                
                // Calculate bonuses (can be enhanced)
                $bonuses = 0;
                
                $netSalary = $baseSalary + $bonuses - $totalDeductions;
                
                $payroll = Payroll::create([
                    'employee_id' => $employeeId,
                    'pay_period_month' => $data['month'],
                    'pay_period_year' => $data['year'],
                    'pay_date' => $data['pay_date'],
                    'basic_salary' => $baseSalary,
                    'bonuses' => $bonuses,
                    'deductions' => $totalDeductions,
                    'net_salary' => $netSalary,
                    'status' => 'pending',
                    'created_by' => auth()->id(),
                ]);
                
                $generated[] = $payroll;
            }
            
            return Response::json([
                'message' => 'Payroll generated successfully',
                'count' => count($generated),
                'payrolls' => $generated,
            ]);
        } catch (\Exception $e) {
            logger()->error('Payroll generation failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to generate payroll'], 500);
        }
    }
    
    /**
     * Show single payroll
     */
    public function show(Request $request, int $id): Response
    {
        $payroll = Payroll::findOrFail($id);
        $payrollModel = Payroll::findOrFail($id);
        
        $employee = $payrollModel->employee();
        
        if ($request->expectsJson()) {
            return Response::json([
                'payroll' => $payroll,
                'employee' => $employee,
            ]);
        }
        
        return view('erp/payroll/show', [
            'payroll' => $payroll,
            'employee' => $employee,
        ]);
    }
    
    /**
     * Approve payroll
     */
    public function approve(Request $request, int $id): Response
    {
        try {
            $payroll = Payroll::findOrFail($id);
            
            if ($payroll['status'] !== 'pending') {
                throw new \Exception('Only pending payroll can be approved');
            }
            
            $payroll->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => date('Y-m-d H:i:s'),
            ]);
            
            return Response::json(['message' => 'Payroll approved successfully']);
        } catch (\Exception $e) {
            logger()->error('Payroll approval failed', ['error' => $e->getMessage(), 'payroll_id' => $id]);
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Process payment
     */
    public function processPayment(Request $request, int $id): Response
    {
        try {
            $payroll = Payroll::findOrFail($id);
            
            if ($payroll['status'] !== 'approved') {
                throw new \Exception('Payroll must be approved before processing payment');
            }
            
            $payroll->update([
                'status' => 'paid',
                'paid_date' => date('Y-m-d'),
            ]);
            
            // TODO: Integrate with payment gateway or accounting system
            
            return Response::json(['message' => 'Payment processed successfully']);
        } catch (\Exception $e) {
            logger()->error('Payment processing failed', ['error' => $e->getMessage(), 'payroll_id' => $id]);
            return Response::json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get payroll summary
     */
    public function summary(Request $request): Response
    {
        $month = $request->get('month', date('n'));
        $year = $request->get('year', date('Y'));
        
        $payrolls = Payroll::where('pay_period_month', $month)
            ->where('pay_period_year', $year)
            ->get();
        
        $summary = [
            'total_employees' => count($payrolls),
            'total_basic_salary' => array_sum(array_column($payrolls, 'basic_salary')),
            'total_bonuses' => array_sum(array_column($payrolls, 'bonuses')),
            'total_deductions' => array_sum(array_column($payrolls, 'deductions')),
            'total_net_salary' => array_sum(array_column($payrolls, 'net_salary')),
            'by_status' => [
                'pending' => 0,
                'approved' => 0,
                'paid' => 0,
            ],
        ];
        
        foreach ($payrolls as $payroll) {
            $status = $payroll['status'];
            if (isset($summary['by_status'][$status])) {
                $summary['by_status'][$status]++;
            }
        }
        
        return Response::json(['summary' => $summary]);
    }
}
