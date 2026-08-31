<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button
        type="button"
        @click="open = ! open"
        class="relative inline-flex items-center rounded-md p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none"
        aria-label="{{ __('Notifications') }}"
    >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        @if (($unreadNotificationsCount ?? 0) > 0)
            <span class="absolute -top-0.5 -end-0.5 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-xs font-bold text-white">
                {{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-transition
        class="absolute z-50 mt-2 w-80 rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 end-0"
        style="display: none;"
    >
        <div class="border-b border-gray-200 dark:border-gray-700 px-4 py-3">
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ __('Notifications') }}</h3>
                <a href="{{ route('notifications.index') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                    {{ __('View all') }}
                </a>
            </div>
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($latestNotifications ?? [] as $notification)
                <div class="border-b border-gray-100 dark:border-gray-700 px-4 py-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ $notification->data['title'] ?? __('Notification') }}
                    </p>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                        {{ $notification->data['message'] ?? '' }}
                    </p>
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="mt-2">
                        @csrf
                        <button type="submit" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                            {{ __('Mark as read') }}
                        </button>
                    </form>
                </div>
            @empty
                <p class="px-4 py-6 text-sm text-gray-600 dark:text-gray-400">{{ __('No unread notifications.') }}</p>
            @endforelse
        </div>
    </div>
</div>
