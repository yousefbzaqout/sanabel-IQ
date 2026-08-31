<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div
            class="min-h-screen bg-gray-100 dark:bg-gray-900"
            @auth
            x-data="parentRealtime({ parentId: {{ auth()->id() }}, initialUnreadCount: {{ $unreadNotificationsCount ?? 0 }} })"
            x-init="init()"
            @endauth
            @keydown.escape.window="toasts = []"
        >
            @include('layouts.navigation')

            <!-- Live Toast Notifications -->
            <div
                class="pointer-events-none fixed top-4 z-[60] flex w-full max-w-sm flex-col gap-3 px-4 {{ app()->getLocale() === 'ar' ? 'start-0' : 'end-0 sm:end-4' }}"
                aria-live="polite"
            >
                <template x-for="(toast, index) in toasts" :key="toast.id">
                    <div
                        x-show="toast.visible"
                        x-transition:enter="transform transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-x-full rtl:-translate-x-full"
                        x-transition:enter-end="opacity-100 translate-x-0"
                        x-transition:leave="transform transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-x-0"
                        x-transition:leave-end="opacity-0 translate-x-full rtl:-translate-x-full"
                        class="pointer-events-auto overflow-hidden rounded-lg border shadow-lg"
                        :class="toast.tone === 'celebration'
                            ? 'border-amber-300 bg-amber-50 text-amber-950 dark:border-amber-700 dark:bg-amber-950/90 dark:text-amber-100'
                            : 'border-emerald-300 bg-emerald-50 text-emerald-950 dark:border-emerald-700 dark:bg-emerald-950/90 dark:text-emerald-100'"
                    >
                        <div class="px-4 py-3 text-sm font-medium" x-text="toast.message"></div>
                    </div>
                </template>
            </div>

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        @stack('scripts')
    </body>
</html>
