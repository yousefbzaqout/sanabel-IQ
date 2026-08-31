<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $material->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-2">
                    <p>{{ __('Type') }}: {{ ucfirst($material->type->value) }}</p>
                    <p>{{ __('Status') }}: {{ ucfirst($material->status->value) }}</p>
                    <p>{{ __('Child') }}: {{ $material->student?->name }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Uploaded') }}: {{ $material->created_at?->toDayDateTimeString() }}</p>

                    <div class="pt-4">
                        <a href="{{ route('dashboard') }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                            {{ __('Back to dashboard') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
