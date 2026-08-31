<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Learning Goals') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($goals as $goal)
                        <div class="p-6 flex items-center justify-between gap-4">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $goal->student->name }}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $goal->subject?->name ?? __('General') }} —
                                    {{ $goal->target_activity_count }} {{ __('activities') }},
                                    {{ $goal->target_xp }} XP
                                </p>
                            </div>
                            <span class="text-sm capitalize">{{ $goal->status->value }}</span>
                        </div>
                    @empty
                        <div class="p-6 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('No learning goals yet.') }}
                        </div>
                    @endforelse
                </div>
            </div>

            {{ $goals->links() }}
        </div>
    </div>
</x-app-layout>
