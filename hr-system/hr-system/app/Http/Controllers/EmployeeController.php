<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use App\Exceptions\BusinessRuleException;
use App\Services\EmployeeService;

class EmployeeController extends Controller
{
    /* =======================
     * INDEX
     * ======================= */
  public function index(Request $request)
{
    $employees = Employee::listQuery($request->only('search', 'department', 'rank', 'sort'))
        ->paginate(10)
        ->withQueryString()
        ->through(function ($employee) {
            // Attach this year's leave entitlement/balance for the table.
            $employee->setAttribute('leave_summary', $employee->leaveSummary());
            // Used by the delete dialog to protect a department's only Head.
            $employee->setAttribute('is_last_head', $employee->isLastHeadWithStaff());
            return $employee;
        });

    $departments = Department::all();

    return Inertia::render('Employees/Index', [
        'employees' => $employees,
        'departments' => $departments,
        'filters' => $request->only('search', 'department', 'rank', 'sort'),
        // Used by the delete dialog to protect the last Admin / HR account.
        'roleCounts' => [
            'admin' => User::where('role', 'admin')->count(),
            'hr' => User::where('role', 'hr')->count(),
        ],
    ]);
}


    /* =======================
     * CREATE
     * ======================= */
    public function create()
    {
        $departments = Department::all();
        return Inertia::render('Employees/Create', compact('departments'));
    }

    /* =======================
     * STORE
     * ======================= */
    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:6',

        // system access
        'role' => 'required|in:employee,hr,admin',

        // department hierarchy
        'rank' => 'required|in:employee,head',

        'department_id' => 'required|exists:departments,id',
        'date_of_birth' => 'required|date',
        'date_hired' => 'required|date',
    ]);

    try {
        app(EmployeeService::class)->create($request->only(
            'name', 'email', 'password', 'role', 'rank', 'department_id', 'date_of_birth', 'date_hired'
        ));
    } catch (BusinessRuleException $e) {
        return back()->withErrors($e->errors ?: ['rank' => $e->getMessage()])->withInput();
    }

    return redirect()
        ->route('employees.index')
        ->with('success', 'Employee created successfully');
}


    /* =======================
     * SHOW (profile page)
     * ======================= */
    public function show(Employee $employee)
    {
        $employee->load(['user', 'department']);
        $employee->setAttribute('leave_summary', $employee->leaveSummary());

        $leaves = $employee->leaveRequests()
            ->with(['headApprover', 'hrApprover'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Employees/Show', compact('employee', 'leaves'));
    }

    /* =======================
     * EDIT
     * ======================= */
    public function edit(Employee $employee)
    {
        $departments = Department::all();
        $employee->load('user');

        return Inertia::render('Employees/Edit', compact('employee', 'departments'));
    }

    /* =======================
     * UPDATE
     * ======================= */
    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $employee->user_id,
            'role' => 'required|in:employee,hr,admin',
            'department_id' => 'required|exists:departments,id',
            'rank' => 'required|in:employee,head',
            'date_of_birth' => 'required|date',
            'date_hired' => 'required|date',
        ]);

        try {
            app(EmployeeService::class)->update($employee, $request->only(
                'name', 'email', 'role', 'rank', 'department_id', 'date_of_birth', 'date_hired'
            ));
        } catch (BusinessRuleException $e) {
            return back()->withErrors($e->errors ?: ['rank' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee updated successfully');
    }

    /* =======================
     * DESTROY
     * ======================= */
    public function destroy(Employee $employee)
    {
        try {
            app(EmployeeService::class)->delete($employee);
        } catch (BusinessRuleException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee deleted successfully');
    }
}
