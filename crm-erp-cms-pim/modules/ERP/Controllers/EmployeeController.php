<?php

namespace Modules\ERP\Controllers;

use Core\Request;
use Core\Response;
use Modules\ERP\Models\HR\Employee;
use Modules\ERP\Models\HR\Attendance;
use Core\Validation\Validator;
use App\Services\AuditService;

/**
 * Employee Controller
 * 
 * Handles employee management and HR operations
 */
class EmployeeController
{
    /**
     * Display list of employees
     */
    public function index(Request $request): Response
    {
        $department = $request->get('department');
        $status = $request->get('status');
        
        $query = \Core\Database\DB::table('erp_employees');
        
        if ($department) {
            $query->where('department', '=', $department);
        }
        
        if ($status) {
            $query->where('status', '=', $status);
        }
        
        $employees = $query->orderBy('first_name', 'ASC')->get();
        $employees = array_map(fn($row) => Employee::hydrate($row), $employees);
        
        if ($request->expectsJson()) {
            return Response::json(['employees' => $employees]);
        }
        
        return view('erp/employees/index', [
            'employees' => $employees,
            'department' => $department,
            'status' => $status,
        ]);
    }
    
    /**
     * Show create form
     */
    public function create(): Response
    {
        return view('erp/employees/create');
    }
    
    /**
     * Store new employee
     */
    public function store(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'employee_code' => 'required|max:50',
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'email' => 'required|email',
            'phone' => 'max:20',
            'department' => 'required|max:100',
            'position' => 'required|max:100',
            'hire_date' => 'required|date',
            'salary' => 'numeric',
            'address' => 'max:500',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return Response::json(['errors' => $validator->errors()], 422);
            }
            return redirect('/erp/employees/create')->with('errors', $validator->errors());
        }
        
        try {
            $data = $validator->validated();
            $data['status'] = 'active';
            
            $employee = Employee::create($data);
            
            AuditService::logCreate('Employee', $employee['id'], $data, auth()->id());
            
            if ($request->expectsJson()) {
                return Response::json(['employee' => $employee, 'message' => 'Employee created successfully'], 201);
            }
            
            return redirect('/erp/employees/' . $employee['id'])->with('success', 'Employee created successfully');
        } catch (\Exception $e) {
            logger()->error('Employee creation failed', ['error' => $e->getMessage()]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to create employee'], 500);
            }
            
            return redirect('/erp/employees/create')->with('error', 'Failed to create employee');
        }
    }
    
    /**
     * Show single employee
     */
    public function show(Request $request, int $id): Response
    {
        $employee = Employee::findOrFail($id);
        
        // Get recent attendance
        $attendance = Attendance::where('employee_id', $id)
            ->orderBy('attendance_date', 'DESC')
            ->limit(10)
            ->get();
        
        if ($request->expectsJson()) {
            return Response::json([
                'employee' => $employee,
                'attendance' => $attendance,
            ]);
        }
        
        return view('erp/employees/show', [
            'employee' => $employee,
            'attendance' => $attendance,
        ]);
    }
    
    /**
     * Show edit form
     */
    public function edit(int $id): Response
    {
        $employee = Employee::findOrFail($id);
        return view('erp/employees/edit', ['employee' => $employee]);
    }
    
    /**
     * Update employee
     */
    public function update(Request $request, int $id): Response
    {
        $validator = Validator::make($request->all(), [
            'employee_code' => 'required|max:50',
            'first_name' => 'required|max:100',
            'last_name' => 'required|max:100',
            'email' => 'required|email',
            'phone' => 'max:20',
            'department' => 'required|max:100',
            'position' => 'required|max:100',
            'salary' => 'numeric',
            'address' => 'max:500',
            'status' => 'required|in:active,inactive,terminated',
        ]);
        
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return Response::json(['errors' => $validator->errors()], 422);
            }
            return redirect("/erp/employees/{$id}/edit")->with('errors', $validator->errors());
        }
        
        try {
            $employee = Employee::findOrFail($id);
            $oldData = $employee;
            
            $employee->update($validator->validated());
            
            AuditService::logUpdate('Employee', $id, $oldData, $validator->validated(), auth()->id());
            
            if ($request->expectsJson()) {
                return Response::json(['employee' => $employee, 'message' => 'Employee updated successfully']);
            }
            
            return redirect('/erp/employees/' . $id)->with('success', 'Employee updated successfully');
        } catch (\Exception $e) {
            logger()->error('Employee update failed', ['error' => $e->getMessage(), 'employee_id' => $id]);
            
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Failed to update employee'], 500);
            }
            
            return redirect("/erp/employees/{$id}/edit")->with('error', 'Failed to update employee');
        }
    }
    
    /**
     * Record attendance
     */
    public function recordAttendance(Request $request): Response
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer',
            'attendance_date' => 'required|date',
            'check_in' => 'required',
            'check_out' => '',
            'status' => 'required|in:present,absent,half_day,late,leave',
        ]);
        
        if ($validator->fails()) {
            return Response::json(['errors' => $validator->errors()], 422);
        }
        
        try {
            $data = $validator->validated();
            
            // Calculate hours worked
            if (!empty($data['check_out'])) {
                $checkIn = strtotime($data['check_in']);
                $checkOut = strtotime($data['check_out']);
                $data['hours_worked'] = ($checkOut - $checkIn) / 3600;
            }
            
            $attendance = Attendance::create($data);
            
            return Response::json(['attendance' => $attendance, 'message' => 'Attendance recorded successfully']);
        } catch (\Exception $e) {
            logger()->error('Attendance recording failed', ['error' => $e->getMessage()]);
            return Response::json(['error' => 'Failed to record attendance'], 500);
        }
    }
}
