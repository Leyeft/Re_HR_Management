<?php

namespace App\Http\Controllers;

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
    $sort = $request->get('sort', 'asc');

    $employees = Employee::join('users', 'employees.user_id', '=', 'users.id')
        ->with(['user', 'department'])

        // Search
        ->when($request->search, function ($query) use ($request) {
            $query->where(function ($q) use ($request) {
                $q->where('users.name', 'like', '%' . $request->search . '%')
                  ->orWhere('users.email', 'like', '%' . $request->search . '%');
            });
        })

        // Department filter
        ->when($request->department !== null && $request->department !== '', function ($q) use ($request) {
            $q->where('employees.department_id', $request->department);
        })

        // Rank filter
        ->when($request->rank !== null && $request->rank !== '', function ($q) use ($request) {
            $q->where('employees.rank', $request->rank);
        })

        ->orderBy('users.name', $sort)
        ->select('employees.*')
        ->paginate(10)
        ->withQueryString();

    $departments = Department::all();

    return view('employees.index', compact('employees', 'departments'));
}


    /* =======================
     * CREATE
     * ======================= */
    public function create()
    {
        $departments = Department::all();
        return view('employees.create', compact('departments'));
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

    // Create employee
    Employee::create([
        'user_id' => $user->id,
        'department_id' => $request->department_id,
        'rank' => $request->rank,
        'date_of_birth' => $request->date_of_birth,
    ]);

    return redirect()
        ->route('employees.index')
        ->with('success', 'Employee created successfully');
}


    /* =======================
     * EDIT
     * ======================= */
    public function edit(Employee $employee)
    {
        $departments = Department::all();
        $employee->load('user');

        return view('employees.edit', compact('employee', 'departments'));
    }

    /* =======================
     * UPDATE
     * ======================= */
    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $employee->user_id,
            'department_id' => 'required|exists:departments,id',
            'rank' => 'required|in:employee,head,admin,hr',
            'date_of_birth' => 'required|date',
        ]);

        // Update user info
        $employee->user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        // Update employee info
        $employee->update([
            'department_id' => $request->department_id,
            'rank' => $request->rank,
            'date_of_birth' => $request->date_of_birth,
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
        // This will also delete employee due to cascade
        $employee->user->delete();

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee deleted successfully');
    }
}
