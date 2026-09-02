<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Learning Hub') }} — {{ $student->name }}
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ __('Weekly Rank') }}: #{{ $weeklyRank }} · {{ __('Streak') }}: {{ $streakDays }} 🔥
            </p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('student.activities.index') }}" class="rounded-2xl bg-indigo-600 p-6 text-white shadow-lg hover:bg-indigo-500 transition">
                    <p class="text-lg font-bold">{{ __('Activities') }}</p>
                    <p class="text-sm opacity-90">{{ __('Play generated learning games') }}</p>
                </a>
                <a href="{{ route('student.progress') }}" class="rounded-2xl bg-violet-600 p-6 text-white shadow-lg hover:bg-violet-500 transition">
                    <p class="text-lg font-bold">{{ __('Progress') }}</p>
                    <p class="text-sm opacity-90">{{ __('XP, levels, milestones') }}</p>
                </a>
                <a href="{{ route('student.leaderboard') }}" class="rounded-2xl bg-amber-500 p-6 text-white shadow-lg hover:bg-amber-400 transition">
                    <p class="text-lg font-bold">{{ __('Leaderboard') }}</p>
                    <p class="text-sm opacity-90">{{ __('Weekly peer rankings') }}</p>
                </a>
                <a href="{{ route('student.badges') }}" class="rounded-2xl bg-emerald-600 p-6 text-white shadow-lg hover:bg-emerald-500 transition">
                    <p class="text-lg font-bold">{{ __('Badges') }}</p>
                    <p class="text-sm opacity-90">{{ __('Unlocked achievements') }}</p>
                </a>
            </div>

            <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Quiz Path') }}</h3>
                @if ($materials->isEmpty())
                    <p class="text-gray-600 dark:text-gray-400">{{ __('No published quizzes for this grade yet.') }}</p>
                @else
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($materials as $material)
                            <a href="{{ route('student.materials.quiz', $material) }}" class="rounded-xl border border-indigo-100 dark:border-indigo-900/40 bg-indigo-50 dark:bg-indigo-950/30 p-4 hover:shadow-md transition">
                                <p class="font-semibold text-indigo-900 dark:text-indigo-100">{{ $material->title }}</p>
                                <p class="mt-1 text-sm text-indigo-700 dark:text-indigo-200">{{ $material->xp_reward }} XP</p>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($badges->isNotEmpty())
                <div class="rounded-2xl bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('Recent Badges') }}</h3>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($badges as $badge)
                            <span class="inline-flex rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-100 px-3 py-1 text-sm font-medium">
                                {{ $badge->name_ar }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
