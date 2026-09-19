<x-brand-layout :title="$title.' — سنابل IQ'">
    <main class="w-full bg-surface min-h-screen pt-28 pb-space-xl px-gutter lg:px-margin-desktop" dir="rtl">
        <article class="max-w-3xl mx-auto space-y-space-lg">
            <header class="space-y-space-sm">
                <a href="{{ url('/') }}" class="inline-flex items-center gap-space-xs font-label-md text-label-md text-secondary hover:underline">
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    العودة إلى الصفحة الرئيسية
                </a>
                <h1 class="font-headline-lg text-headline-lg text-on-surface font-bold">{{ $title }}</h1>
                <p class="font-body-sm text-body-sm text-on-surface-variant">آخر تحديث: {{ $updatedAt }}</p>
            </header>

            <div class="space-y-space-md">
                @foreach ($sections as $section)
                    <section class="rounded-2xl bg-surface-container-lowest p-space-md shadow-sm space-y-space-xs">
                        <h2 class="font-headline-sm text-headline-sm text-on-surface font-semibold">{{ $section['heading'] }}</h2>
                        <p class="font-body-md text-body-md text-on-surface-variant leading-relaxed">{{ $section['body'] }}</p>
                    </section>
                @endforeach
            </div>

            <footer class="pt-space-md border-t border-surface-container flex flex-wrap gap-space-md">
                <a href="{{ route('legal.privacy') }}" class="font-label-md text-label-md text-on-surface-variant hover:text-primary">سياسة الخصوصية الأكاديمية</a>
                <a href="{{ route('legal.terms') }}" class="font-label-md text-label-md text-on-surface-variant hover:text-primary">شروط الخدمة للمدارس</a>
                <a href="{{ route('legal.compliance') }}" class="font-label-md text-label-md text-on-surface-variant hover:text-primary">خارطة الحماية والامتثال</a>
            </footer>
        </article>
    </main>
</x-brand-layout>
