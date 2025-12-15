<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            My Leave Requests
        </h2>
    </x-slot>

    <div class="py-8 max-w-6xl mx-auto">

        <div class="flex justify-end mb-4">
            <a href="{{ route('leave.create') }}"
               class="bg-indigo-600 text-white px-4 py-2 rounded">
                + Request Leave
            </a>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-100 text-left text-sm">
                    <tr>
                        <th class="p-3">Dates</th>
                        <th class="p-3">Reason</th>
                        <th class="p-3">Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($leaves as $leave)
                        <tr class="border-t">
                            <td class="p-3">
                                {{ $leave->start_date }} → {{ $leave->end_date }}
                            </td>

                            <td class="p-3">
                                {{ $leave->reason }}
                            </td>

                            <td class="p-3">
                                @if ($leave->status === 'pending')
                                    <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded">
                                        Pending
                                    </span>

                                @elseif ($leave->status === 'approved')
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-700 rounded">
                                        Approved
                                    </span>

                                @else
                                    <span class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded">
                                        Rejected
                                    </span>

                                    @if ($leave->rejection_reason)
                                        <div class="text-xs text-gray-500 mt-1">
                                            Reason: {{ $leave->rejection_reason }}
                                        </div>
                                    @endif
                                @endif
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-6 text-center text-gray-500">
                                No leave requests yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $leaves->links() }}
        </div>
    </div>
</x-app-layout>
