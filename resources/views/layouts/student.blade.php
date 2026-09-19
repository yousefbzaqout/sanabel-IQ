<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'سنابل IQ') }} — بوابة الأبطال الصغار</title>
        <link rel="icon" href="{{ asset('brand/logo.svg') }}" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Tajawal:wght@500;700;800;900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-body-md text-on-surface antialiased bg-background min-h-screen flex flex-col selection:bg-primary-container selection:text-on-primary-container" data-student-layout data-sanabel-audio-boot>
        @include('layouts.student-navigation')

        <main class="w-full flex-1 pb-24 md:pb-space-xl {{ isset($header) ? 'pt-0' : 'pt-20' }}">
            @isset($header)
                <div class="pt-20">
                    <header class="bg-surface-container-lowest/90 backdrop-blur-xl shadow-[0_4px_20px_-2px_rgba(15,23,42,0.06)]">
                        <div class="max-w-7xl mx-auto py-4 px-gutter">
                            {{ $header }}
                        </div>
                    </header>
                </div>
            @endisset

            {{ $slot }}
        </main>

        @include('layouts.student-bottom-nav')

        <footer class="w-full bg-surface-container-lowest py-space-lg border-t border-surface-container-low hidden md:block">
            <div class="max-w-7xl mx-auto px-gutter flex flex-col sm:flex-row items-center justify-between gap-space-md text-on-surface-variant text-body-sm">
                <span>🌱 سنابل IQ — رحلة تعليمية ذكية وتفاعلية ممتعة للأطفال</span>
                <a href="{{ route('student.adults-portal') }}" class="font-bold hover:text-on-surface transition-colors">بوابة أولياء الأمور</a>
            </div>
        </footer>

        @stack('scripts')
    </body>
</html>
