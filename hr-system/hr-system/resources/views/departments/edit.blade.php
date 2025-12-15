<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200">
            Edit Department
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            <form method="POST" action="{{ route('departments.update', $department) }}">
                @csrf
                @method('PUT')

                @include('departments._form', ['department' => $department])

                <div class="flex justify-end mt-6">
                    <x-primary-button>
                        Update Department
                    </x-primary-button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
