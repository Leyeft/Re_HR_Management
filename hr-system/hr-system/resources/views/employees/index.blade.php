<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            Employees
        </h2>
    </x-slot>
<form method="GET"
      class="mt-4 mb-2 flex flex-col md:flex-row md:items-center md:justify-between gap-3">

    {{-- LEFT: Search --}}
    <div class="w-full md:w-1/3 md:ml-6">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="Search name or email..."
            class="w-full border-gray-300 rounded-md shadow-sm text-sm py-2"
        >
    </div>

    {{-- RIGHT: Filters --}}
    <div class="flex items-center gap-2">

        {{-- Department --}}
        <select name="department"
                class="border-gray-300 rounded-md shadow-sm text-sm py-2">
            <option value="">All Departments</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}"
                    {{ request('department') == $department->id ? 'selected' : '' }}>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>

        {{-- Rank --}}
        <select name="rank"
                class="border-gray-300 rounded-md shadow-sm text-sm py-2">
            <option value="">All Ranks</option>
            <option value="employee" {{ request('rank') === 'employee' ? 'selected' : '' }}>
                Employee
            </option>
            <option value="head" {{ request('rank') === 'head' ? 'selected' : '' }}>
                Head
            </option>
        </select>

        {{-- Sort toggle --}}
        <button type="submit"
                name="sort"
                value="{{ request('sort') === 'asc' ? 'desc' : 'asc' }}"
                class="border border-gray-300 rounded-md px-3 py-2 text-sm hover:bg-gray-100">
            {{ request('sort') === 'asc' ? 'Z–A' : 'A–Z' }}
        </button>

        {{-- Apply --}}
        <button type="submit"
                class="bg-indigo-600 text-white px-3 py-2 rounded-md text-sm hover:bg-indigo-700">
            Apply
        </button>
    </div>
</form>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">

                {{-- Success Message --}}
                @if (session('success'))
                    <div class="mb-4 p-3 text-sm text-green-700 bg-green-100 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Header + Action --}}
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-700 dark:text-gray-200">
                        Employee List
                    </h3>

                    <a href="{{ route('employees.create') }}"
                       class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
                        + Add Employee
                    </a>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto mt-0">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Name
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Email
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Department
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                    Rank
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                                    Actions
                                </th>
                            </tr>

                        </thead>

                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($employees as $employee)
                                <tr>
                                    <td class="px-4 py-3">
                                        {{ $employee->user->name }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $employee->user->email }}
                                    </td>

                                    <td class="px-4 py-3">
                                        {{ $employee->department->name ?? '—' }}
                                    </td>

                                    <td class="px-4 py-3">
                                        @if ($employee->rank === 'head')
                                            <span class="px-2 py-1 text-xs font-semibold text-purple-700 bg-purple-100 rounded">
                                                Head
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold text-blue-700 bg-blue-100 rounded">
                                                Employee
                                            </span>
                                        @endif
                                    </td>

                                <td class="px-4 py-3 text-right space-x-3">
                                    <a href="{{ route('employees.edit', $employee) }}" class="text-indigo-600 hover:underline">
                                        Edit
                                    </a>

                                    <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button onclick="return confirm('Delete this employee?')" class="text-red-600 hover:underline">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                        No employees found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- Pagination --}}
                <div class="mt-4">
                    {{ $employees->links() }}
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
