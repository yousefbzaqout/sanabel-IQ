<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Laravel') }} — {{ __('Admin') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-100 text-slate-900">
        <div class="min-h-screen">
            <nav class="border-b border-slate-200 bg-white">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-6">
                        <a href="{{ route('admin.subjects.index') }}" class="text-lg font-semibold text-indigo-700">
                            {{ __('Curriculum Admin') }}
                        </a>
                        <a href="{{ route('admin.subjects.index') }}" class="text-sm text-slate-600 hover:text-indigo-700">
                            {{ __('Subjects') }}
                        </a>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-slate-600">
                        <span>{{ Auth::user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-indigo-700 hover:underline">{{ __('Log Out') }}</button>
                        </form>
                    </div>
                </div>
            </nav>

            @isset($header)
                <header class="bg-white shadow-sm">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
        @stack('scripts')
    </body>
</html>
