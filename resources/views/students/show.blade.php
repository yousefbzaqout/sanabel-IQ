<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $student->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <p>{{ __('Grade') }}: {{ $student->grade_level }}</p>
                    <p>{{ __('School term') }}: {{ $student->school_term }}</p>
                    @if ($student->avatar_path)
                        <p>{{ __('Avatar') }}: {{ $student->avatar_path }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
