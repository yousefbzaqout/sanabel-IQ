<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Notifications') }}
            </h2>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <x-secondary-button type="submit">
                        {{ __('Mark all as read') }}
                    </x-secondary-button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($notifications as $notification)
                        <div class="p-6 {{ $notification->read_at ? 'opacity-70' : '' }}">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $notification->data['title'] ?? __('Notification') }}
                                    </p>
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $notification->data['message'] ?? '' }}
                                    </p>
                                    @if (! empty($notification->data['children']))
                                        <ul class="mt-3 space-y-2 text-sm text-gray-700 dark:text-gray-300">
                                            @foreach ($notification->data['children'] as $child)
                                                <li>
                                                    {{ $child['name'] ?? '' }} —
                                                    {{ $child['activities_completed'] ?? 0 }} {{ __('activities') }},
                                                    {{ $child['xp_earned'] ?? 0 }} XP
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                @if ($notification->read_at === null)
                                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                        @csrf
                                        <x-secondary-button type="submit">
                                            {{ __('Mark as read') }}
                                        </x-secondary-button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-sm text-gray-600 dark:text-gray-400">
                            {{ __('No notifications yet.') }}
                        </div>
                    @endforelse
                </div>
            </div>

            {{ $notifications->links() }}
        </div>
    </div>
</x-app-layout>
