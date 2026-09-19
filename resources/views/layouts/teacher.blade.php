<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'سنابل IQ — بوابة المعلم' }}</title>
        <link rel="icon" href="{{ asset('brand/logo.svg') }}" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Tajawal:wght@500;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-background font-body-md text-on-surface antialiased selection:bg-secondary/20 selection:text-on-surface">
        @include('layouts.teacher-navigation')

        <main class="mx-auto w-full max-w-7xl px-gutter py-space-lg">
            @isset($header)
                <div class="mb-space-lg">
                    {{ $header }}
                </div>
            @endisset

            {{ $slot }}
        </main>
    </body>
</html>
