@props([
    'text' => null,
])

<button
    type="button"
    {{ $attributes->merge([
        'class' => 'inline-flex h-9 w-9 items-center justify-center rounded-full border border-amber-200 bg-amber-50 text-lg text-amber-800 shadow-sm transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100 dark:hover:bg-amber-900',
    ]) }}
    @if (filled($text))
        @click="$store.audio.speak(@js($text))"
    @else
        @click="$store.audio.toggleMute()"
    @endif
    @if (! filled($text))
        data-audio-toggle
        data-audio-storage-key="sanabel_audio_muted"
        :aria-pressed="$store.audio.muted.toString()"
        :title="$store.audio.muted ? 'تفعيل الصوت' : 'كتم الصوت'"
        :aria-label="$store.audio.muted ? 'تفعيل الصوت' : 'كتم الصوت'"
    @else
        aria-label="سماع النص"
        title="🔊 سماع"
    @endif
>
    @if (filled($text))
        <span aria-hidden="true">🔊</span>
    @else
        <span x-text="$store.audio.muted ? '🔇' : '🔊'" aria-hidden="true"></span>
    @endif
</button>
