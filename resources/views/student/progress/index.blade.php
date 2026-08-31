<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Progress Dashboard') }}
            </h2>
            <a href="{{ route('student.leaderboard') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                {{ __('View Grade Leaderboard') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 overflow-hidden shadow-sm sm:rounded-lg text-white">
                <div class="p-8 space-y-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-indigo-100">{{ $student->name }}</p>
                            <h3 class="text-3xl font-bold">{{ __('Level :level', ['level' => $level]) }}</h3>
                            <p class="mt-2 text-indigo-100">{{ __('Grade rank #:rank in grade :grade', ['rank' => $gradeRank, 'grade' => $student->grade_level]) }}</p>
                        </div>
                        <div class="text-end">
                            <p class="text-sm text-indigo-100">{{ __('Total XP') }}</p>
                            <p class="text-4xl font-bold">{{ $student->total_xp }}</p>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span>{{ __('Progress to next level') }}</span>
                            <span>{{ $xpTowardsNextLevel }} / 100 XP</span>
                        </div>
                        <div class="h-3 rounded-full bg-indigo-400/40 overflow-hidden">
                            <div class="h-full bg-white transition-all duration-300" style="width: {{ $progressPercent }}%"></div>
                        </div>
                        <p class="mt-2 text-sm text-indigo-100">
                            {{ __(':xp XP needed for Level :level', ['xp' => $xpRequiredForNextLevel, 'level' => $level + 1]) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium mb-4">{{ __('Badges') }}</h3>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ($badges as $badgeEntry)
                                <div @class([
                                    'rounded-lg border p-4',
                                    'border-amber-300 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-700' => $badgeEntry['unlocked'],
                                    'border-gray-200 bg-gray-50 opacity-70 dark:bg-gray-900 dark:border-gray-700' => ! $badgeEntry['unlocked'],
                                ])>
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $badgeEntry['badge']->name }}</p>
                                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $badgeEntry['badge']->description }}</p>
                                        </div>
                                        <span class="text-2xl">{{ $badgeEntry['unlocked'] ? '🏅' : '🔒' }}</span>
                                    </div>
                                    @if ($badgeEntry['unlocked'] && $badgeEntry['unlocked_at'])
                                        <p class="mt-3 text-xs text-amber-700 dark:text-amber-300">
                                            {{ __('Unlocked') }}: {{ $badgeEntry['unlocked_at']->diffForHumans() }}
                                        </p>
                                    @elseif ($badgeEntry['progress_hint'])
                                        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">{{ $badgeEntry['progress_hint'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 space-y-4">
                        <h3 class="text-lg font-medium">{{ __('Recent Activity Summary') }}</h3>
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-900 p-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Accuracy') }}</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $accuracyPercent }}%</p>
                        </div>

                        @if ($recentAttempts->isEmpty())
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('No completed activities yet.') }}</p>
                        @else
                            <ul class="space-y-3">
                                @foreach ($recentAttempts as $attempt)
                                    <li class="rounded-lg border border-gray-200 dark:border-gray-700 p-3">
                                        <p class="font-medium text-gray-900 dark:text-gray-100">{{ $attempt->activity?->title ?? __('Activity') }}</p>
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            {{ $attempt->score }}/{{ $attempt->total_questions }} — {{ $attempt->xp_earned }} XP
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
