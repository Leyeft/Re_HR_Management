<x-app-layout>
    <div class="max-w-4xl mx-auto py-10">
        <h1 class="text-2xl font-bold mb-6">Create Employee</h1>

        <form method="POST" action="{{ route('employees.store') }}" class="space-y-4">
            @csrf

            <input name="name" placeholder="Full Name"
                   class="w-full border rounded p-2" required>

            <input name="email" type="email" placeholder="Email"
                   class="w-full border rounded p-2" required>

            <input name="password" type="password" placeholder="Password"
                   class="w-full border rounded p-2" required>

            <input name="date_of_birth" type="date"
                   class="w-full border rounded p-2" required>

            {{-- System Role --}}
            <select name="role" class="w-full border rounded p-2" required>
                <option value="employee">Employee</option>
                <option value="hr">HR</option>
                <option value="admin">Admin</option>
            </select>

            {{-- Department --}}
            <select name="department_id" class="w-full border rounded p-2" required>
                <option value="">Select Department</option>
                @foreach ($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                @endforeach
            </select>

            {{-- Department Rank --}}
            <select name="rank" class="w-full border rounded p-2" required>
                <option value="employee">Employee</option>
                <option value="head">Head of Department</option>
            </select>

            <button class="bg-blue-600 text-white px-4 py-2 rounded">
                Create Employee
            </button>
        </form>
    </div>
</x-app-layout>
