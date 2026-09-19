<x-student-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Grade :grade Leaderboard', ['grade' => $gradeLevel]) }}
            </h2>
            <a href="{{ route('student.progress') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                {{ __('Back to Progress') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="rounded-lg border border-indigo-200 bg-indigo-50 dark:bg-indigo-900/20 dark:border-indigo-800 p-4">
                <p class="text-sm text-indigo-800 dark:text-indigo-200">
                    {{ __('Your current rank in grade :grade is #:rank with :xp XP.', [
                        'grade' => $gradeLevel,
                        'rank' => $activeRank,
                        'xp' => $student->total_xp,
                    ]) }}
                </p>
            </div>

            @if ($entries->isEmpty())
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 text-gray-600 dark:text-gray-400">
                    {{ __('No leaderboard entries yet for this grade level.') }}
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($entries as $entry)
                            @php
                                $isActiveChild = $entry['student']->is($student);
                            @endphp
                            <div @class([
                                'flex items-center justify-between gap-4 p-4',
                                'bg-indigo-50 dark:bg-indigo-900/20 ring-2 ring-indigo-400' => $isActiveChild,
                            ])>
                                <div class="flex items-center gap-4">
                                    <div @class([
                                        'flex h-12 w-12 items-center justify-center rounded-full text-lg font-bold',
                                        'bg-amber-400 text-amber-950' => $entry['rank'] === 1,
                                        'bg-gray-300 text-gray-800' => $entry['rank'] === 2,
                                        'bg-orange-300 text-orange-950' => $entry['rank'] === 3,
                                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' => $entry['rank'] > 3,
                                    ])>
                                        #{{ $entry['rank'] }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $entry['display_name'] }}
                                            @if ($isActiveChild)
                                                <span class="ms-2 text-xs font-medium text-indigo-600 dark:text-indigo-300">({{ __('You') }})</span>
                                            @endif
                                        </p>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ __('Level :level', ['level' => $entry['level']]) }}
                                            @if (($entry['current_streak'] ?? 0) > 0)
                                                · 🔥 {{ $entry['current_streak'] }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <p class="text-lg font-bold text-amber-600 dark:text-amber-300">{{ ($entry['total_xp'] ?? $entry['student']->total_xp) }} XP</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-student-layout>
