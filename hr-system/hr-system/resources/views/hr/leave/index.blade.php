<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">All Leave Requests</h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto">
        <div class="bg-white shadow rounded">
            <table class="w-full">
                <thead class="bg-gray-100 text-sm">
                    <tr>
                        <th class="p-3">Employee</th>
                        <th class="p-3">Department</th>
                        <th class="p-3">Dates</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($leaves as $leave)
                        <tr class="border-t">
                            <td class="p-3">
                                {{ $leave->employee->user->name }}
                            </td>
                            <td class="p-3">
                                {{ $leave->employee->department->name }}
                            </td>
                            <td class="p-3">
                                {{ $leave->start_date }} → {{ $leave->end_date }}
                            </td>
                            <td class="p-3">
                                {{ ucfirst($leave->status) }}
                                @if ($leave->status === 'rejected')
                                    <div class="text-xs text-gray-500">
                                        {{ $leave->rejection_reason }}
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $leaves->links() }}
        </div>
    </div>
</x-app-layout>
