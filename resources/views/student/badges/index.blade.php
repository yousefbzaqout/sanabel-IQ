<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Trophy Case') }}
            </h2>
            <a href="{{ route('student.progress') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                {{ __('Back to Progress') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-lg border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 p-4">
                <p class="text-sm text-amber-900 dark:text-amber-100">
                    {{ __('Current streak: :current days · Best streak: :max days', [
                        'current' => $currentStreak,
                        'max' => $maxStreak,
                    ]) }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($badges as $entry)
                    <div @class([
                        'rounded-lg border p-5 shadow-sm',
                        'border-emerald-300 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/20' => $entry['unlocked'],
                        'border-gray-200 bg-white opacity-70 dark:border-gray-700 dark:bg-gray-800' => ! $entry['unlocked'],
                    ])>
                        <div class="flex items-start gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 text-xl dark:bg-indigo-900/40">
                                🏅
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $entry['badge']->name_ar }}</h3>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $entry['badge']->description_ar }}</p>
                                @if ($entry['unlocked'])
                                    <p class="mt-2 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                                        {{ __('Unlocked') }} · {{ $entry['unlocked_at']?->format('Y-m-d') }}
                                    </p>
                                @else
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('Locked') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
