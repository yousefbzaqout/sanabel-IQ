@props([
    'text' => null,
    'url' => null,
])

<button
    type="button"
    x-data
    {{ $attributes->merge([
        'class' => 'inline-flex min-h-12 min-w-12 items-center justify-center rounded-full border border-amber-200 bg-amber-50 text-lg text-amber-800 shadow-sm transition hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-400 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100 dark:hover:bg-amber-900',
    ]) }}
    @if (filled($text) || filled($url))
        @if (filled($text))
            data-tts-text="{{ $text }}"
        @endif
        @if (filled($url))
            data-static-audio="{{ $url }}"
        @endif
        @click="(() => {
            const audio = $store.audio;
            if (!audio) { console.warn('[sanabel-audio] store missing on speak'); return; }
            const url = @js($url);
            const text = @js($text);
            if (url && audio.playStatic) { audio.playStatic(url, text || ''); return; }
            if (text) { audio.speakFast?.(text) ?? audio.speak?.(text); }
        })()"
        aria-label="سماع النص"
        title="🔊 سماع"
    @else
        @click="(() => { const audio = $store.audio; if (!audio?.toggleMute) { console.warn('[sanabel-audio] store missing on toggle'); return; } audio.unlock?.(); audio.toggleMute(); })()"
        data-audio-toggle
        data-audio-storage-key="sanabel_audio_muted"
        :aria-pressed="($store.audio?.muted ?? false).toString()"
        :title="$store.audio?.muted ? 'تفعيل الصوت' : 'كتم الصوت'"
        :aria-label="$store.audio?.muted ? 'تفعيل الصوت' : 'كتم الصوت'"
    @endif
>
    @if (filled($text) || filled($url))
        <span aria-hidden="true">🔊</span>
    @else
        <span x-text="$store.audio?.muted ? '🔇' : '🔊'" aria-hidden="true"></span>
    @endif
</button>
