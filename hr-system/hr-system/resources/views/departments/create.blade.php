<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            Create Department
        </h2>
    </x-slot>

    <div class="py-6 max-w-xl mx-auto">
        <form method="POST" action="{{ route('departments.store') }}">
            @csrf

            {{-- Department Name --}}
            <div>
                <label class="block">Department Name</label>
                <input
                    type="text"
                    name="name"
                    class="w-full border p-2 mt-1"
                    value="{{ old('name') }}"
                    required
                >
            </div>

            {{-- Description --}}
            <div class="mt-4">
                <label class="block">Description (Optional)</label>
                <textarea
                    name="description"
                    class="w-full border p-2 mt-1"
                    rows="3"
                >{{ old('description') }}</textarea>
            </div>

            <button class="mt-4 bg-green-600 text-black px-4 py-2 rounded">
                Save
            </button>
        </form>
    </div>
</x-app-layout>
