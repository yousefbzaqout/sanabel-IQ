<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? config('app.name', 'سنابل IQ') }}</title>
        <link rel="icon" href="{{ asset('brand/logo.svg') }}" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Tajawal:wght@500;700;800;900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-body-md text-body-md text-on-surface antialiased bg-background min-h-dvh overflow-y-auto selection:bg-primary-fixed selection:text-on-primary-fixed pb-[env(safe-area-inset-bottom,1rem)]" data-sanabel-audio-boot data-quiz-shell>
        {{ $slot }}
        @livewireScriptConfig
        @stack('scripts')
    </body>
</html>
