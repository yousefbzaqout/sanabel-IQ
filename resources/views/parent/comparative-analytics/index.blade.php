<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Comparative Analytics') }}
            </h2>
            <a href="{{ route('parent.analytics') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                {{ __('Single Child Analytics') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($children === [])
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Add children to compare their progress side-by-side.') }}
                </div>
            @else
                <div class="grid gap-6 lg:grid-cols-2">
                    @foreach ($children as $childEntry)
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 space-y-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $childEntry['student']->name }}</h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Grade') }} {{ $childEntry['student']->grade_level }} · {{ __('Level') }} {{ $childEntry['level'] }}</p>
                                    </div>
                                    <a href="{{ route('parent.students.export', ['student' => $childEntry['student'], 'format' => 'csv']) }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                        {{ __('Export CSV') }}
                                    </a>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="rounded-lg bg-indigo-50 dark:bg-indigo-900/20 p-4">
                                        <p class="text-sm text-indigo-700 dark:text-indigo-300">{{ __('Total XP') }}</p>
                                        <p class="text-2xl font-bold text-indigo-900 dark:text-indigo-100">{{ $childEntry['total_xp'] }}</p>
                                    </div>
                                    <div class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 p-4">
                                        <p class="text-sm text-emerald-700 dark:text-emerald-300">{{ __('Accuracy') }}</p>
                                        <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-100">{{ $childEntry['overall_accuracy_percent'] }}%</p>
                                    </div>
                                </div>
                                @if ($childEntry['weak_topics'] !== [])
                                    <div class="rounded-lg border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 p-3">
                                        <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ __('Weak areas') }}</p>
                                        <ul class="mt-2 space-y-1 text-sm text-red-700 dark:text-red-300">
                                            @foreach ($childEntry['weak_topics'] as $topic)
                                                <li>{{ $topic['subject'] }} — {{ $topic['accuracy_percent'] }}%</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('Side-by-Side Comparison') }}</h3>
                    <div class="h-80">
                        <canvas id="comparativeChart"></canvas>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if ($children !== [])
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const canvas = document.getElementById('comparativeChart');
                    if (!canvas) return;

                    new Chart(canvas, {
                        type: 'bar',
                        data: {
                            labels: @json($chartLabels),
                            datasets: [
                                {
                                    label: @json(__('Accuracy %')),
                                    data: @json($accuracyValues),
                                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                                    borderRadius: 8,
                                },
                                {
                                    label: 'XP',
                                    data: @json($xpValues),
                                    backgroundColor: 'rgba(99, 102, 241, 0.7)',
                                    borderRadius: 8,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: { y: { beginAtZero: true } },
                        },
                    });
                });
            </script>
        @endpush
    @endif
</x-app-layout>
