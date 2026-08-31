<x-admin-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-slate-800">{{ __('Subjects by Grade') }}</h2>
    </x-slot>

    <div class="space-y-6">
        <div class="flex flex-wrap gap-2">
            @foreach (range(1, 5) as $grade)
                <a
                    href="{{ route('admin.subjects.index', ['grade' => $grade]) }}"
                    class="rounded-md px-4 py-2 text-sm font-medium {{ $gradeLevel === $grade ? 'bg-indigo-600 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50' }}"
                >
                    @switch($grade)
                        @case(1) الصف الأول @break
                        @case(2) الصف الثاني @break
                        @case(3) الصف الثالث @break
                        @case(4) الصف الرابع @break
                        @case(5) الصف الخامس @break
                    @endswitch
                </a>
            @endforeach
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-4 text-lg font-medium">{{ __('Create Subject') }}</h3>
                <form method="POST" action="{{ route('admin.subjects.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="code" :value="__('Code')" />
                        <x-text-input id="code" name="code" class="mt-1 block w-full" :value="old('code')" placeholder="MATH-G3" required />
                        <x-input-error :messages="$errors->get('code')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="grade_level" :value="__('Grade')" />
                        <select id="grade_level" name="grade_level" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" required>
                            @foreach (range(1, 5) as $grade)
                                <option value="{{ $grade }}" @selected((int) old('grade_level', $gradeLevel) === $grade)>{{ $grade }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('grade_level')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="icon" :value="__('Icon')" />
                        <x-text-input id="icon" name="icon" class="mt-1 block w-full" :value="old('icon')" />
                    </div>
                    <div>
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">{{ old('description') }}</textarea>
                    </div>
                    <x-primary-button>{{ __('Create Subject') }}</x-primary-button>
                </form>
            </section>

            <section class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h3 class="mb-4 text-lg font-medium">{{ __('Subjects') }} — {{ __('Grade') }} {{ $gradeLevel }}</h3>
                <div class="space-y-3">
                    @forelse ($subjects as $subject)
                        <div class="flex items-center justify-between rounded-md border border-slate-200 px-4 py-3">
                            <div>
                                <a href="{{ route('admin.subjects.show', $subject) }}" class="font-medium text-indigo-700 hover:underline">
                                    {{ $subject->name }}
                                </a>
                                <p class="text-xs text-slate-500">{{ $subject->code }} · {{ $subject->learning_materials_count }} {{ __('materials') }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.subjects.destroy', $subject) }}" onsubmit="return confirm('{{ __('Delete this subject?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:underline">{{ __('Delete') }}</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('No subjects for this grade yet.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-admin-layout>
