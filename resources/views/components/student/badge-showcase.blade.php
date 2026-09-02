@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Badge> $badges */
    $badges = collect($badges)->values();
    $badgePayload = $badges->map(fn ($badge) => [
        'id' => $badge->id,
        'code' => $badge->code,
        'name' => $badge->name_ar,
        'description' => $badge->description_ar,
        'icon' => $badge->icon,
    ])->all();
@endphp

@if ($badges->isNotEmpty())
    <div
        data-badge-showcase
        class="mx-auto w-full max-w-md"
        x-data="{
            badges: @js($badgePayload),
            index: 0,
            timer: null,
            init() {
                $store.audio?.playFx('fanfare');
                if (this.badges.length > 1) {
                    this.timer = setInterval(() => {
                        this.index = (this.index + 1) % this.badges.length;
                    }, 3000);
                }
            },
            destroy() {
                if (this.timer) clearInterval(this.timer);
            },
            get current() {
                return this.badges[this.index] ?? null;
            }
        }"
        x-init="init()"
    >
        <template x-if="current">
            <div
                class="relative overflow-hidden rounded-3xl border border-amber-300/60 bg-gradient-to-br from-amber-400/30 via-yellow-300/20 to-orange-400/30 p-6 text-center shadow-[0_0_40px_rgba(251,191,36,0.35)] backdrop-blur"
                wire:ignore
            >
                <div class="pointer-events-none absolute inset-0 animate-pulse bg-[radial-gradient(circle_at_30%_20%,rgba(255,255,255,0.35),transparent_55%)]"></div>
                <div class="pointer-events-none absolute -inset-8 animate-[spin_8s_linear_infinite] bg-[conic-gradient(from_0deg,transparent,rgba(255,255,255,0.25),transparent_40%)] opacity-40"></div>

                <div class="relative z-10 space-y-3" dir="rtl">
                    <p class="text-sm font-semibold uppercase tracking-wide text-amber-100">شارة جديدة!</p>
                    <div
                        class="mx-auto flex h-28 w-28 items-center justify-center rounded-full bg-white/20 text-5xl shadow-inner ring-4 ring-amber-200/70"
                        x-bind:data-badge-code="current.code"
                    >
                        <span x-text="current.icon === 'star' ? '⭐' : (current.icon === 'fire' ? '🔥' : '🏅')"></span>
                    </div>
                    <h3 class="text-2xl font-black text-white" x-text="current.name"></h3>
                    <p class="text-sm leading-relaxed text-amber-50/90" x-text="current.description"></p>
                    <p class="text-xs text-amber-100/80" x-show="badges.length > 1">
                        <span x-text="index + 1"></span> / <span x-text="badges.length"></span>
                    </p>
                </div>
            </div>
        </template>

        @foreach ($badges as $badge)
            <span class="sr-only" data-badge-code="{{ $badge->code }}">{{ $badge->name_ar }} — {{ $badge->description_ar }}</span>
        @endforeach
    </div>
@endif
