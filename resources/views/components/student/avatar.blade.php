<div
    {{ $attributes->class([
        'inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full font-semibold text-white',
        $sizeClass,
    ]) }}
    data-student-avatar
    data-avatar-fallback="{{ filled($imageUrl) ? 'false' : 'true' }}"
    @if (! filled($imageUrl))
        style="background-color: {{ $backgroundColor }};"
        role="img"
        aria-label="صورة {{ $student->name }}"
    @endif
>
    @if (filled($imageUrl))
        <img
            src="{{ $imageUrl }}"
            alt="{{ $student->name }}"
            class="h-full w-full object-cover"
        />
    @else
        <span aria-hidden="true">{{ $initial }}</span>
    @endif
</div>
