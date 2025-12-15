<?php

// app/Http/Controllers/DepartmentController.php

namespace App\Http\Controllers;

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
        $departments = Department::orderBy('name')->get();
        return view('departments.index', compact('departments'));
    }

    // --- C (Create - Form) ---
    public function create()
    {
        return view('departments.create');
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
    $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
    ]);

    $department->update($request->only('name', 'description'));

    return redirect()
        ->route('departments.index')
        ->with('success', 'Department updated successfully');
}


    // --- D (Delete) ---
    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('departments.index')
                         ->with('success', 'Department deleted successfully.');
    }
    public function edit(Department $department)
{
    return view('departments.edit', compact('department'));
}

}
