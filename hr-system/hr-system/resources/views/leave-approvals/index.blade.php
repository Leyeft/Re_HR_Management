<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Leave Approvals</h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto">

        @if (session('success'))
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white shadow rounded">
            <table class="w-full">
                <thead class="bg-gray-100 text-sm">
                    <tr>
                        <th class="p-3 text-left">Employee</th>
                        <th class="p-3">Dates</th>
                        <th class="p-3">Reason</th>
                        <th class="p-3 text-right">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($requests as $leave)
                        <tr class="border-t">
                            <td class="p-3">
                                {{ $leave->employee->user->name }}
                            </td>
                            <td class="p-3">
                                {{ $leave->start_date }} → {{ $leave->end_date }}
                            </td>
                            <td class="p-3">
                                {{ $leave->reason }}
                            </td>
                            <td class="p-3 text-right space-x-2">
                                <form method="POST" action="{{ route('leave.approve', $leave) }}" class="inline">
                                    @csrf
                                    <button class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                        Approve
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('leave.reject', $leave) }}" class="inline">
                                    @csrf
                                    <input type="text"
                                           name="rejection_reason"
                                           placeholder="Reject reason"
                                           required
                                           class="border rounded px-2 py-1 text-sm">
                                    <button class="bg-red-600 text-white px-3 py-1 rounded text-sm">
                                        Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-6 text-center text-gray-500">
                                No pending requests.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $requests->links() }}
        </div>
    </div>
</x-app-layout>
