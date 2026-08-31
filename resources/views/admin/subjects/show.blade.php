<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-800">{{ $subject->name }}</h2>
                <p class="text-sm text-slate-500">{{ $subject->code }} · {{ __('Grade') }} {{ $subject->grade_level }}</p>
            </div>
            <a href="{{ route('admin.subjects.index', ['grade' => $subject->grade_level]) }}" class="text-sm text-indigo-700 hover:underline">
                {{ __('Back to subjects') }}
            </a>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="materialReorder()">
        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h3 class="mb-4 text-lg font-medium">{{ __('Update Subject') }}</h3>
            <form method="POST" action="{{ route('admin.subjects.update', $subject) }}" class="grid gap-4 md:grid-cols-2">
                @csrf
                @method('PUT')
                <div>
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $subject->name)" required />
                </div>
                <div>
                    <x-input-label for="code" :value="__('Code')" />
                    <x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code', $subject->code)" required />
                </div>
                <div>
                    <x-input-label for="grade_level" :value="__('Grade')" />
                    <select id="grade_level" name="grade_level" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" required>
                        @foreach (range(1, 5) as $grade)
                            <option value="{{ $grade }}" @selected((int) old('grade_level', $subject->grade_level) === $grade)>{{ $grade }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="icon" :value="__('Icon')" />
                    <x-text-input id="icon" name="icon" class="mt-1 block w-full" :value="old('icon', $subject->icon)" />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="description" :value="__('Description')" />
                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">{{ old('description', $subject->description) }}</textarea>
                </div>
                <div>
                    <x-primary-button>{{ __('Save Subject') }}</x-primary-button>
                </div>
            </form>
        </section>

        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h3 class="mb-4 text-lg font-medium">{{ __('Create Learning Material') }}</h3>
            <form method="POST" action="{{ route('admin.subjects.materials.store', $subject) }}" class="grid gap-4 md:grid-cols-2">
                @csrf
                <div class="md:col-span-2">
                    <x-input-label for="title" :value="__('Title')" />
                    <x-text-input id="title" name="title" class="mt-1 block w-full" :value="old('title')" required />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="material_description" :value="__('Description')" />
                    <textarea id="material_description" name="description" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">{{ old('description') }}</textarea>
                </div>
                <div>
                    <x-input-label for="xp_reward" :value="__('XP Reward')" />
                    <x-text-input id="xp_reward" type="number" name="xp_reward" min="10" max="500" class="mt-1 block w-full" :value="old('xp_reward', 50)" required />
                </div>
                <div class="flex items-end gap-3">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300 text-indigo-600" @checked(old('is_published'))>
                        {{ __('Published') }} / منشور
                    </label>
                </div>
                <div>
                    <x-primary-button>{{ __('Add Material') }}</x-primary-button>
                </div>
            </form>
        </section>

        <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h3 class="text-lg font-medium">{{ __('Learning Materials') }}</h3>
                <form method="POST" action="{{ route('admin.materials.reorder') }}">
                    @csrf
                    <template x-for="id in orderedIds" :key="id">
                        <input type="hidden" name="ordered_ids[]" :value="id">
                    </template>
                    <x-secondary-button type="submit">{{ __('Save Order') }}</x-secondary-button>
                </form>
            </div>

            <ul class="space-y-3" x-ref="list">
                @forelse ($subject->learningMaterials as $material)
                    <li
                        class="rounded-md border border-slate-200 px-4 py-3"
                        data-id="{{ $material->id }}"
                        draggable="true"
                        @dragstart="dragStart($event)"
                        @dragover.prevent
                        @drop="drop($event)"
                    >
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-medium text-slate-900">{{ $material->title }}</p>
                                <p class="text-xs text-slate-500">
                                    XP {{ $material->xp_reward }} ·
                                    {{ $material->is_published ? 'منشور' : 'مسودة' }} ·
                                    #{{ $material->order_column }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.materials.questions.index', $material) }}" class="text-sm text-indigo-700 hover:underline">
                                    {{ __('Questions') }}
                                </a>
                                <span class="cursor-move text-xs text-slate-400">⇅</span>
                                <form method="POST" action="{{ route('admin.materials.destroy', $material) }}" onsubmit="return confirm('{{ __('Delete this material?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('Delete') }}</button>
                                </form>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">{{ __('No learning materials yet.') }}</li>
                @endforelse
            </ul>
        </section>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('materialReorder', () => ({
                    orderedIds: @json($subject->learningMaterials->pluck('id')->values()),
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
