<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Learning Activities') }}
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $student->name }} — {{ __('Total XP') }}: {{ $student->total_xp }}
            </p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($activities->isEmpty())
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-600 dark:text-gray-400">
                        {{ __('No published activities are available for this child yet.') }}
                    </div>
                </div>
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($activities as $activity)
                        @php
                            $questionCount = count($activity->payload['questions'] ?? []);
                            $latestAttempt = $activity->attempts->first();
                        @endphp
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="p-6 space-y-4">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $activity->title }}
                                    </h3>
                                    @if ($activity->parentMaterial)
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            {{ $activity->parentMaterial->title }}
                                        </p>
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200">
                                        {{ $questionCount }} {{ __('questions') }}
                                    </span>
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                                        {{ $activity->xp_reward }} XP
                                    </span>
                                    @if ($latestAttempt)
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200">
                                            {{ __('Completed') }} — {{ $latestAttempt->score }}/{{ $latestAttempt->total_questions }}
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200">
                                            {{ __('Ready to play') }}
                                        </span>
                                    @endif
                                </div>

                                <a
                                    href="{{ route('student.activities.play', $activity) }}"
                                    class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 transition"
                                >
                                    {{ $latestAttempt ? __('Play Again') : __('Start Activity') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
