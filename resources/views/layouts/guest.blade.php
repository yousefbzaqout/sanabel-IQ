<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'سنابل IQ') }}</title>
        <link rel="icon" href="{{ asset('brand/logo.svg') }}" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-body-md text-on-surface antialiased bg-surface">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-gutter">
            <div class="mb-6">
                <x-brand.logo variant="official" :show-tagline="true" />
            </div>

            <div class="w-full sm:max-w-md mt-2 px-6 py-6 bg-surface-container-lowest shadow-brand-card overflow-hidden rounded-3xl border border-outline-variant/20">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
