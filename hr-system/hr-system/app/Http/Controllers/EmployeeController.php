<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

    // ❌ Block invalid combinations
    if ($request->role !== 'employee' && $request->rank === 'head') {
        return back()->withErrors([
            'rank' => 'Only employees can be Head of Department.',
        ])->withInput();
    }

    // Create user
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role' => $request->role, // ✅ FIXED
    ]);

    // Create employee with an auto-generated, department-based code.
    Employee::create([
        'user_id' => $user->id,
        'department_id' => $request->department_id,
        'employee_code' => Employee::generateCode((int) $request->department_id),
        'rank' => $request->rank,
        'date_of_birth' => $request->date_of_birth,
        'date_hired' => $request->date_hired,
    ]);

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

        $user = $employee->user;

        // Same rule as on create: HR/Admin accounts can't also be a Head.
        if ($request->role !== 'employee' && $request->rank === 'head') {
            return back()->withErrors([
                'rank' => 'Only employees can be Head of Department.',
            ])->withInput();
        }

        // Don't let a role change remove the last Admin or the last HR.
        foreach (['admin' => 'Admin', 'hr' => 'HR'] as $role => $label) {
            if ($user->role === $role && $request->role !== $role
                && User::where('role', $role)->count() <= 1) {
                return back()->withErrors([
                    'role' => "This is the only {$label} account. Give another person the {$label} role first.",
                ])->withInput();
            }
        }

        // Don't leave a department without a Head while it still has staff
        // (their leave requests would have nobody to approve them).
        $stopsBeingHead = $request->rank !== 'head'
            || (int) $request->department_id !== $employee->department_id;

        if ($stopsBeingHead && $employee->isLastHeadWithStaff()) {
            return back()->withErrors([
                'rank' => 'This is the only Head of ' . $employee->department->name
                    . '. Make someone else in that department Head first.',
            ])->withInput();
        }

        // Update user info
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
        ]);

        // Update employee info
        $employee->update([
            'department_id' => $request->department_id,
            'rank' => $request->rank,
            'date_of_birth' => $request->date_of_birth,
            'date_hired' => $request->date_hired,
        ]);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee updated successfully');
    }

    /* =======================
     * DESTROY
     * ======================= */
    public function destroy(Employee $employee)
    {
        $user = $employee->user;

        // Never leave the system without an HR account to sign off leave.
        if ($user->role === 'hr' && User::where('role', 'hr')->count() <= 1) {
            return back()->with('error', '🛡️ "A knight never strikes the last healer." ’Tis the final HR of the kingdom! Slay them, and who shall tend the wounded spirits of the payroll-stricken? Assign another HR first.');
        }

        // Never leave the system without an admin to run it.
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', '👑 "The crown cannot be left upon an empty throne!" Thou wouldst dethrone the last admin of this realm? Crown a worthy heir first, lest the castle gates lock forever (and nobody gets paid on Friday).');
        }

        // Never leave a department with staff but no Head to approve their leave.
        if ($employee->isLastHeadWithStaff()) {
            return back()->with('error', '⚔️ "A castle without a lord falls by nightfall!" This is the only Head of '
                . $employee->department->name . '. Crown a new Head for that department first, '
                . 'or their leave requests shall wander the halls forever.');
        }

        // This will also delete the employee row due to cascade.
        $user->delete();

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee deleted successfully');
    }
}
