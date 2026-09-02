<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Weekly Progress Analytics') }}
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $student->name }} — {{ __('Grade') }} {{ $student->grade_level }}
            </p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ route('parent.analytics.export.pdf', ['student' => $student, 'period' => $period]) }}"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                >
                    {{ __('Download PDF Report') }}
                </a>
                <a
                    href="{{ route('parent.analytics.export.excel', ['student' => $student, 'period' => $period]) }}"
                    class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
                >
                    {{ __('Download Excel Report') }}
                </a>
                <a href="{{ route('parent.analytics') }}" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 dark:text-gray-200">
                    {{ __('Subject Analytics') }}
                </a>
            </div>

            <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('XP Earned This Period') }}</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-600">{{ $summary->xpEarnedInPeriod }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Quiz Accuracy') }}</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-600">{{ $summary->quizAccuracyPercent }}%</p>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Completed Goals') }}</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-600">{{ $summary->completedGoalsCount }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Active Streak') }}</p>
                    <p class="mt-2 text-3xl font-bold text-indigo-600">{{ $summary->currentStreak }}</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('Badge Achievements') }}</h3>
                    <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-200">
                        @forelse ($summary->badgesUnlocked as $badge)
                            <li>{{ $badge['name'] }}</li>
                        @empty
                            <li class="text-gray-500">{{ __('No badges unlocked in this period.') }}</li>
                        @endforelse
                    </ul>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('Parent Goal Checklist') }}</h3>
                    <ul class="space-y-2 text-sm text-gray-700 dark:text-gray-200">
                        @forelse ($summary->parentGoals as $goal)
                            <li>{{ $goal['title'] }} — {{ __($goal['status']) }}</li>
                        @empty
                            <li class="text-gray-500">{{ __('No goals configured yet.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">{{ __('Quiz History') }}</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-right">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="py-2 pe-4">{{ __('Quiz') }}</th>
                                <th class="py-2 pe-4">{{ __('Completed At') }}</th>
                                <th class="py-2 pe-4">{{ __('Score') }}</th>
                                <th class="py-2">{{ __('XP') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($summary->quizHistory as $attempt)
                                <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                    <td class="py-2 pe-4">{{ $attempt['title'] }}</td>
                                    <td class="py-2 pe-4">{{ $attempt['completed_at'] }}</td>
                                    <td class="py-2 pe-4">{{ $attempt['score_percentage'] }}%</td>
                                    <td class="py-2">{{ $attempt['xp_earned'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-gray-500">{{ __('No quiz attempts in this period.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
