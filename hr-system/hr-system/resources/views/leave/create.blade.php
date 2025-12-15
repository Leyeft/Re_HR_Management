<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Request Leave
        </h2>
    </x-slot>

    <div class="py-8 max-w-xl mx-auto">
        <div class="bg-white shadow rounded-lg p-6">

            <form method="POST" action="{{ route('leave.store') }}">
                @csrf

                <div>
                    <label class="block text-sm font-medium">Start Date</label>
                    <input type="date" name="start_date"
                           class="w-full border rounded mt-1 p-2"
                           required>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium">End Date</label>
                    <input type="date" name="end_date"
                           class="w-full border rounded mt-1 p-2"
                           required>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium">Reason</label>
                    <textarea name="reason"
                              rows="4"
                              class="w-full border rounded mt-1 p-2"
                              required></textarea>
                </div>

                <div class="flex justify-end mt-6">
                    <button class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                        Submit Request
                    </button>
                </div>

            </form>
        </div>
    </div>
</x-app-layout>
