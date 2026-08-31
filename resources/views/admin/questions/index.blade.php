<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-800">{{ __('Question Builder') }}</h2>
                <p class="text-sm text-slate-500">{{ $material->title }} · {{ $material->subject?->name }}</p>
            </div>
            <a href="{{ route('admin.subjects.show', $material->subject_id) }}" class="text-sm text-indigo-700 hover:underline">
                {{ __('Back to material') }}
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section
            class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200"
            x-data="questionBuilder({
                type: '{{ old('type', 'mcq') }}',
                options: @js(old('options', [
                    ['option_text' => '', 'is_correct' => true],
                    ['option_text' => '', 'is_correct' => false],
                ]))
            })"
        >
            <h3 class="mb-4 text-lg font-medium">{{ __('Create Question') }}</h3>

            @if ($errors->any())
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.materials.questions.store', $material) }}" class="space-y-4">
                @csrf

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="type" :value="__('Question Type')" />
                        <select id="type" name="type" x-model="type" @change="syncTypeDefaults()" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" required>
                            <option value="mcq">اختيار من متعدد (MCQ)</option>
                            <option value="true_false">صح / خطأ</option>
                            <option value="fill_blank">املأ الفراغ</option>
                        </select>
                    </div>
                    <div>
                        <x-input-label for="points" :value="__('Points')" />
                        <x-text-input id="points" type="number" name="points" min="1" max="100" class="mt-1 block w-full" :value="old('points', 10)" required />
                    </div>
                </div>

                <div>
                    <x-input-label for="prompt" :value="__('Prompt')" />
                    <textarea id="prompt" name="prompt" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" required>{{ old('prompt') }}</textarea>
                </div>

                <div>
                    <x-input-label for="explanation" value="تلميح / الشرح للطفل" />
                    <textarea id="explanation" name="explanation" rows="2" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" placeholder="يظهر بعد إجابة الطفل">{{ old('explanation') }}</textarea>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="font-medium text-slate-800">{{ __('Options') }}</h4>
                        <button
                            type="button"
                            x-show="type !== 'true_false'"
                            @click="addOption()"
                            class="text-sm font-medium text-indigo-700 hover:underline"
                        >
                            + إضافة خيار
                        </button>
                    </div>

                    <template x-for="(option, index) in options" :key="index">
                        <div class="flex flex-col gap-2 rounded-md border border-slate-200 p-3 sm:flex-row sm:items-center">
                            <input type="hidden" :name="`options[${index}][is_correct]`" :value="option.is_correct ? 1 : 0">
                            <input
                                type="text"
                                class="block w-full rounded-md border-slate-300 shadow-sm"
                                :name="`options[${index}][option_text]`"
                                x-model="option.option_text"
                                :placeholder="type === 'true_false' ? (index === 0 ? 'صح' : 'خطأ') : 'نص الخيار'"
                                required
                            >
                            <label class="inline-flex items-center gap-2 whitespace-nowrap text-sm text-emerald-700">
                                <input
                                    type="radio"
                                    name="correct_option_marker"
                                    class="border-slate-300 text-emerald-600"
                                    :checked="option.is_correct"
                                    @change="markCorrect(index)"
                                >
                                علامة الإجابة الصحيحة
                            </label>
                            <button
                                type="button"
                                class="text-sm text-red-600 hover:underline"
                                x-show="type !== 'true_false' && options.length > 2"
                                @click="removeOption(index)"
                            >
                                حذف
                            </button>
                        </div>
                    </template>
                </div>

                <x-primary-button>{{ __('Save Question') }}</x-primary-button>
            </form>
        </section>

        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200" x-data="questionReorder()">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-lg font-medium">{{ __('Questions') }}</h3>
                @if ($material->questions->isNotEmpty())
                    <form method="POST" action="{{ route('admin.materials.questions.reorder', $material) }}">
                        @csrf
                        <template x-for="id in orderedIds" :key="id">
                            <input type="hidden" name="ordered_ids[]" :value="id">
                        </template>
                        <x-secondary-button type="submit">{{ __('Save Order') }}</x-secondary-button>
                    </form>
                @endif
            </div>

            <ul class="space-y-3" x-ref="list">
                @forelse ($material->questions as $question)
                    <li
                        class="rounded-md border border-slate-200 px-4 py-3"
                        data-id="{{ $question->id }}"
                        draggable="true"
                        @dragstart="dragStart($event)"
                        @dragover.prevent
                        @drop="drop($event)"
                    >
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="font-medium text-slate-900">{{ $question->prompt }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $question->type->value }} · {{ $question->points }} {{ __('points') }} · #{{ $question->order_column }}
                                </p>
                                <ul class="mt-2 space-y-1 text-sm text-slate-600">
                                    @foreach ($question->options as $option)
                                        <li>
                                            {{ $option->option_text }}
                                            @if ($option->is_correct)
                                                <span class="font-medium text-emerald-700">(✓ الإجابة الصحيحة)</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                                @if ($question->explanation)
                                    <p class="mt-2 text-xs text-slate-500">تلميح: {{ $question->explanation }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="cursor-move text-xs text-slate-400">⇅</span>
                                <form method="POST" action="{{ route('admin.questions.destroy', $question) }}" onsubmit="return confirm('{{ __('Delete this question?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('Delete') }}</button>
                                </form>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">{{ __('No questions yet.') }}</li>
                @endforelse
            </ul>
        </section>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('questionBuilder', (initial) => ({
                    type: initial.type,
                    options: initial.options.map((option) => ({
                        option_text: option.option_text ?? '',
                        is_correct: Boolean(Number(option.is_correct)),
                    })),
                    syncTypeDefaults() {
                        if (this.type === 'true_false') {
                            this.options = [
                                { option_text: 'صح', is_correct: true },
                                { option_text: 'خطأ', is_correct: false },
                            ];
                            return;
                        }

                        if (this.options.length < 2) {
                            this.options = [
                                { option_text: '', is_correct: true },
                                { option_text: '', is_correct: false },
                            ];
                        }
                    },
                    addOption() {
                        this.options.push({ option_text: '', is_correct: false });
                    },
                    removeOption(index) {
                        if (this.options.length <= 2) {
                            return;
                        }
                        const wasCorrect = this.options[index].is_correct;
                        this.options.splice(index, 1);
                        if (wasCorrect && this.options.length > 0) {
                            this.options[0].is_correct = true;
                        }
                    },
                    markCorrect(index) {
                        this.options = this.options.map((option, optionIndex) => ({
                            ...option,
                            is_correct: optionIndex === index,
                        }));
                    },
                }));

                Alpine.data('questionReorder', () => ({
                    orderedIds: @json($material->questions->pluck('id')->values()),
                    dragStart(event) {
                        event.dataTransfer.setData('text/plain', event.currentTarget.dataset.id);
                    },
                    drop(event) {
                        const draggedId = Number(event.dataTransfer.getData('text/plain'));
                        const targetId = Number(event.currentTarget.dataset.id);
                        if (!draggedId || draggedId === targetId) {
                            return;
                        }

                        const from = this.orderedIds.indexOf(draggedId);
                        const to = this.orderedIds.indexOf(targetId);
                        if (from === -1 || to === -1) {
                            return;
                        }

                        this.orderedIds.splice(from, 1);
                        this.orderedIds.splice(to, 0, draggedId);

                        const list = this.$refs.list;
                        const nodes = Array.from(list.children);
                        const dragged = nodes.find((node) => Number(node.dataset.id) === draggedId);
                        const target = nodes.find((node) => Number(node.dataset.id) === targetId);
                        if (dragged && target) {
                            list.insertBefore(dragged, target);
                        }
                    },
                }));
            });
        </script>
    @endpush
</x-admin-layout>
