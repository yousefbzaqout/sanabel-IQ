@props([
    'variant' => 'mark', // mark | official | wordmark
    'showTagline' => false,
])

@php
    $src = match ($variant) {
        'official' => asset('brand/logo-official.svg'),
        'wordmark' => asset('brand/logo-ar.svg'),
        default => asset('brand/logo.svg'),
    };
@endphp

<a href="{{ url('/') }}" {{ $attributes->class(['inline-flex items-center gap-space-sm no-underline']) }}>
    <img
        src="{{ $src }}"
        alt="سنابل IQ"
        class="h-8 w-auto object-contain"
        width="120"
        height="40"
    />
    @if ($showTagline)
        <div class="flex flex-col leading-tight">
            <span class="font-headline-sm text-headline-sm text-primary tracking-tight font-bold">سنابل IQ</span>
            <span class="font-label-sm text-label-sm text-tertiary">منظومة التعليم الذكي K-12</span>
        </div>
    @endif
</a>
