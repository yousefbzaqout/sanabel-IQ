<div class="flex flex-wrap items-center gap-3">
    @php($children = auth()->user()?->students ?? collect())

    @if ($children->isNotEmpty())
        <label class="text-sm font-medium text-gray-700 dark:text-gray-200" for="active-child-select">
            الابن النشط
        </label>
        <select
            id="active-child-select"
            wire:model="selectedStudentId"
            wire:change="switchActiveChild"
            class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
        >
            @foreach ($children as $child)
                <option value="{{ $child->id }}">{{ $child->name }}</option>
            @endforeach
        </select>

        <a
            href="{{ route('student.dashboard') }}"
            class="inline-flex items-center rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500"
        >
            بدء التعلم / خوض الاختبارات
        </a>
    @endif
</div>
