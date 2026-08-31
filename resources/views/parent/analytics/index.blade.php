<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Parent Analytics Dashboard') }}
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $student->name }} — {{ __('Grade') }} {{ $student->grade_level }}
            </p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg lg:col-span-1">
                    <div class="p-6 flex flex-col items-center text-center">
                        <h3 class="text-lg font-medium mb-4">{{ __('Overall Accuracy') }}</h3>
                        <div class="relative h-36 w-36">
                            <svg class="h-36 w-36 -rotate-90" viewBox="0 0 36 36" aria-hidden="true">
                                <circle cx="18" cy="18" r="15.9155" fill="none" class="stroke-gray-200 dark:stroke-gray-700" stroke-width="3" />
                                <circle
                                    cx="18"
                                    cy="18"
                                    r="15.9155"
                                    fill="none"
                                    class="stroke-indigo-500"
                                    stroke-width="3"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ $analysis['overall_accuracy_percent'] }}, 100"
                                />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ $analysis['overall_accuracy_percent'] }}%</span>
                            </div>
                        </div>
                        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ __(':count questions attempted', ['count' => $analysis['total_questions_attempted']]) }}
                        </p>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg lg:col-span-2">
                    <div class="p-6">
                        <h3 class="text-lg font-medium mb-4">{{ __('Subject Performance') }}</h3>
                        @if ($analysis['subject_breakdown'] === [])
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('No activity attempts recorded yet.') }}</p>
                        @else
                            <div class="h-64 mb-6">
                                <canvas id="subjectPerformanceChart" aria-label="{{ __('Subject performance chart') }}"></canvas>
                            </div>
                            <div class="space-y-3">
                                @foreach ($analysis['subject_breakdown'] as $subject)
                                    @php
                                        $accuracy = $subject['accuracy_percent'];
                                        $badgeClass = match (true) {
                                            $accuracy > 75 => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200',
                                            $accuracy >= 60 => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200',
                                            default => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200',
                                        };
                                    @endphp
                                    <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                                        <div>
                                            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $subject['subject'] }}</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ __(':correct / :total correct across :attempts attempts', [
                                                    'correct' => $subject['correct_answers'],
                                                    'total' => $subject['total_questions'],
                                                    'attempts' => $subject['attempts_count'],
                                                ]) }}
                                            </p>
                                        </div>
                                        <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $badgeClass }}">
                                            {{ $subject['accuracy_percent'] }}%
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            @if ($weakTopics->isNotEmpty())
                <div class="rounded-lg border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 p-6">
                    <h3 class="text-lg font-semibold text-red-800 dark:text-red-200 mb-3">{{ __('Weak Areas Requiring Focus') }}</h3>
                    <ul class="space-y-2">
                        @foreach ($weakTopics as $topic)
                            <li class="text-sm text-red-700 dark:text-red-300">
                                {{ $topic['subject'] }} — {{ $topic['accuracy_percent'] }}% {{ __('accuracy') }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-medium">{{ __('Smart Teacher Recommendations for Parents') }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('توصيات المعلم الذكي للأب') }}</p>
                        </div>
                        <form method="POST" action="{{ route('parent.analytics.generate-recommendations') }}">
                            @csrf
                            <x-primary-button type="submit">
                                {{ __('Generate AI Study Plan') }}
                            </x-primary-button>
                        </form>
                    </div>

                    @if ($latestRecommendation)
                        <div class="rounded-lg border border-indigo-200 bg-indigo-50 dark:bg-indigo-900/20 dark:border-indigo-800 p-5 space-y-4">
                            <div>
                                <p class="text-sm text-indigo-700 dark:text-indigo-300">{{ __('Suggested Focus Area') }}</p>
                                <p class="mt-1 text-lg font-semibold text-indigo-900 dark:text-indigo-100">
                                    {{ $latestRecommendation->suggested_focus_area }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-indigo-800 dark:text-indigo-200 mb-2">{{ __('Actionable Tips') }}</p>
                                <ul class="list-disc ps-5 space-y-2 text-sm text-indigo-900 dark:text-indigo-100">
                                    @foreach ($latestRecommendation->actionable_tips_json as $tip)
                                        <li>{{ $tip }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <p class="text-xs text-indigo-600 dark:text-indigo-300">
                                {{ __('Generated') }}: {{ $latestRecommendation->generated_at->diffForHumans() }}
                            </p>
                        </div>
                    @else
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Generate personalized study recommendations based on your child\'s weak topics and recent performance.') }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if ($analysis['subject_breakdown'] !== [])
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const canvas = document.getElementById('subjectPerformanceChart');
                    if (!canvas) {
                        return;
                    }

                    new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels: @json($chartLabels),
                            datasets: [{
                                label: @json(__('Accuracy %')),
                                data: @json($chartValues),
                                backgroundColor: @json($chartValues).map(function (value) {
                                    if (value > 75) {
                                        return 'rgba(34, 197, 94, 0.7)';
                                    }

                                    if (value >= 60) {
                                        return 'rgba(234, 179, 8, 0.7)';
                                    }

                                    return 'rgba(239, 68, 68, 0.7)';
                                }),
                                borderRadius: 8,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 100,
                                },
                            },
                            plugins: {
                                legend: {
                                    display: false,
                                },
                            },
                        },
                    });
                });
            </script>
        @endpush
    @endif
</x-app-layout>
