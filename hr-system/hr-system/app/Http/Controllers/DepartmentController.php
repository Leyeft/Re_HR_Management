<?php

// app/Http/Controllers/DepartmentController.php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Department;
use Illuminate\Http\Request;


class DepartmentController extends Controller
{
    // Secure the controller: Only accessible when logged in
    /*
    public function __construct()
    {
        $this->middleware('auth');
    }
    */
    // --- R (Read - Index/List) ---
    public function index()
    {
        $departments = Department::withCount('employees')->orderBy('name')->get();
        return Inertia::render('Departments/Index', compact('departments'));
    }

    // --- C (Create - Form) ---
    public function create()
    {
        return Inertia::render('Departments/Create');
    }

    // --- C (Create - Store) ---
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'description' => 'nullable|string',
        ]);

        Department::create($request->all());

        return redirect()->route('departments.index')
                         ->with('success', 'Department created successfully.');
    }

    // --- U (Update - Form) ---


    // --- U (Update - Save) ---
    public function update(Request $request, Department $department)
{
    // Name must stay unique (ignoring this department's own current name).
    $request->validate([
        'name' => 'required|string|max:255|unique:departments,name,' . $department->id,
        'description' => 'nullable|string',
    ], [
        'name.unique' => 'Another department already has this name.',
    ]);

    $department->update($request->only('name', 'description'));

    return redirect()
        ->route('departments.index')
        ->with('success', 'Department updated successfully');
}


    // --- D (Delete) ---
    public function destroy(Department $department)
    {
        // Can't delete a department that still has employees.
        if ($department->employees()->exists()) {
            return back()->with('error',
                '🏰 "Halt! The castle still has residents." Thou wouldst demolish a department with '
                . 'employees still inside? Even the cruelest king evacuates first. Move them elsewhere, '
                . 'then return with thy wrecking ram.'
            );
        }

        $department->delete();

        return redirect()->route('departments.index')
                         ->with('success', 'Department deleted successfully.');
    }
    public function edit(Department $department)
{
    return Inertia::render('Departments/Edit', compact('department'));
}

}
