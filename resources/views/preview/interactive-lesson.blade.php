<x-quiz-layout :title="$lessonTitle.' — معاينة عامة'">
    <div class="mx-auto max-w-5xl px-4 pt-4">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-amber-500/15 px-4 py-3 text-amber-100 ring-1 ring-amber-400/30">
            <p class="text-sm font-medium">
                معاينة عامة لدرس تفاعلي من سنابل IQ — جرّب المحطات الست بدون تسجيل دخول.
            </p>
            <a
                href="{{ url('/#demo-request-section') }}"
                class="inline-flex items-center gap-1 rounded-full bg-amber-500 px-4 py-2 text-sm font-bold text-slate-950 hover:bg-amber-400"
            >
                اطلب عرضاً لمدرستك
            </a>
        </div>
    </div>

    <livewire:student.interactive-lesson-demo :lesson-key="$lessonKey" />
</x-quiz-layout>
