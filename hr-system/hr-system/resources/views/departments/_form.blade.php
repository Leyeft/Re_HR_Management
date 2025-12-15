<div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">

    {{-- Department Name --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            Department Name
        </label>
        <input
            type="text"
            name="name"
            class="w-full mt-1 border-gray-300 rounded-md shadow-sm
                   focus:ring-indigo-500 focus:border-indigo-500
                   dark:bg-gray-900 dark:border-gray-700 dark:text-gray-200"
            value="{{ old('name', $department->name ?? '') }}"
            required
        >
        @error('name')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Description --}}
    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">
            Description (Optional)
        </label>
        <textarea
            name="description"
            rows="3"
            class="w-full mt-1 border-gray-300 rounded-md shadow-sm
                   focus:ring-indigo-500 focus:border-indigo-500
                   dark:bg-gray-900 dark:border-gray-700 dark:text-gray-200"
        >{{ old('description', $department->description ?? '') }}</textarea>
        @error('description')
            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
        @enderror
    </div>

</div>
