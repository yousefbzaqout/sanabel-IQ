@props(['name' => 'spa', 'filled' => false])

<span
    {{ $attributes->class(['material-symbols-outlined']) }}
    @if ($filled) style="font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24" @endif
    aria-hidden="true"
>{{ $name }}</span>
