<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Edit Employee
        </h2>
    </x-slot>

    <div class="py-8 max-w-4xl mx-auto">
        <div class="bg-white shadow rounded-lg p-6">

            <form method="POST" action="{{ route('employees.update', $employee->id) }}">
                @csrf
                @method('PUT')

                <div class="mb-4">
                    <label class="block text-sm font-medium">Name</label>
                    <input type="text"
                           name="name"
                           class="w-full border rounded p-2 mt-1"
                           value="{{ old('name', $employee->user->name) }}"
                           required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium">Email</label>
                    <input type="email"
                           name="email"
                           class="w-full border rounded p-2 mt-1"
                           value="{{ old('email', $employee->user->email) }}"
                           required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium">Date of Birth</label>
                    <input type="date"
                           name="date_of_birth"
                           class="w-full border rounded p-2 mt-1"
                           value="{{ old('date_of_birth', $employee->date_of_birth) }}"
                           required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium">Department</label>
                    <select name="department_id" class="w-full border rounded p-2 mt-1">
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}"
                                {{ $employee->department_id == $department->id ? 'selected' : '' }}>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium">Rank</label>
                    <select name="rank" class="w-full border rounded p-2 mt-1">
                        <option value="employee" {{ $employee->rank === 'employee' ? 'selected' : '' }}>
                            Employee
                        </option>
                        <option value="head" {{ $employee->rank === 'head' ? 'selected' : '' }}>
                            Head of Department
                        </option>
                    </select>
                </div>

                <div class="flex justify-end">
                    <button class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                        Update Employee
                    </button>
                </div>

            </form>

        </div>
    </div>
</x-app-layout>
