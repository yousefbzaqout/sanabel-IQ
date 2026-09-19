<div class="flex flex-wrap items-center gap-space-sm" dir="rtl">
    @php($children = auth()->user()?->students ?? collect())

    @if ($children->isNotEmpty())
        <div class="flex items-center gap-space-sm bg-surface-container-low px-space-md py-1.5 rounded-full shadow-[0_2px_8px_rgba(15,23,42,0.04)]">
            <span class="material-symbols-outlined text-primary-container text-lg">face</span>
            <label class="font-label-md text-label-md text-on-surface-variant" for="active-child-select">
                الطفل النشط:
            </label>
            <select
                id="active-child-select"
                wire:model="selectedStudentId"
                wire:change="switchActiveChild"
                class="min-h-11 bg-transparent border-0 font-label-lg text-label-lg text-on-surface font-bold focus:ring-0 focus:outline-none cursor-pointer"
                style="min-height: 2.25rem; height: 2.25rem;"
            >
                @foreach ($children as $child)
                    <option value="{{ $child->id }}">{{ $child->name }}</option>
                @endforeach
            </select>
        </div>

        <a
            href="{{ route('student.dashboard') }}"
            class="inline-flex items-center gap-1.5 rounded-full bg-primary-container px-4 py-2 font-label-md text-label-md font-bold text-on-primary-container shadow-sm hover:brightness-105 transition-all"
        >
            <span class="material-symbols-outlined text-base">rocket_launch</span>
            بدء التعلم / خوض الاختبارات
        </a>
    @endif
</div>
