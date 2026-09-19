<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
            <x-primary-button
                x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'upload-material')"
            >
                {{ __('Upload Material') }}
            </x-primary-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-md">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 space-y-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ __('Active Learning Goals') }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Track weekly targets for the active child.') }}</p>
                        </div>
                        <x-primary-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'create-goal')">
                            {{ __('إضافة هدف دراسي جديد') }}
                        </x-primary-button>
                    </div>

                    @forelse ($activeGoals as $goalEntry)
                        <div class="rounded-lg border border-indigo-200 dark:border-indigo-800 p-4 space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $goalEntry['goal']->subject?->name ?? __('General Goal') }}
                                </p>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $goalEntry['goal']->start_date->toDateString() }} — {{ $goalEntry['goal']->end_date->toDateString() }}
                                </span>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span>{{ __('Activities') }}</span>
                                    <span>{{ $goalEntry['activities_completed'] }} / {{ $goalEntry['goal']->target_activity_count }}</span>
                                </div>
                                <div class="h-2 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                    <div class="h-full bg-indigo-500" style="width: {{ $goalEntry['activity_progress_percent'] }}%"></div>
                                </div>
                            </div>
                            @if ($goalEntry['goal']->target_xp > 0)
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span>XP</span>
                                        <span>{{ $goalEntry['xp_earned'] }} / {{ $goalEntry['goal']->target_xp }}</span>
                                    </div>
                                    <div class="h-2 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                        <div class="h-full bg-amber-500" style="width: {{ $goalEntry['xp_progress_percent'] }}%"></div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('No active learning goals yet.') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-4">{{ __('Uploaded materials') }}</h3>

                    @if ($materials->isEmpty())
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('No materials uploaded yet for this child.') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Title') }}</th>
                                        <th class="px-4 py-2 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Type') }}</th>
                                        <th class="px-4 py-2 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                                        <th class="px-4 py-2 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Uploaded') }}</th>
                                        <th class="px-4 py-2 text-end text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($materials as $material)
                                        <tr>
                                            <td class="px-4 py-3 text-sm">
                                                <a href="{{ route('materials.show', $material) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                                    {{ $material->title }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                @php
                                                    $typeClasses = match ($material->type->value) {
                                                        'exam' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
                                                        'summary' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-200',
                                                        'worksheet' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
                                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                                    };
                                                @endphp
                                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $typeClasses }}">
                                                    {{ ucfirst($material->type->value) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                @php
                                                    $statusClasses = match ($material->status->value) {
                                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-200',
                                                        'processing' => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
                                                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
                                                        'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
                                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                                    };
                                                @endphp
                                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClasses }}">
                                                    {{ ucfirst($material->status->value) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                {{ $material->created_at?->diffForHumans() }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-end space-x-3 rtl:space-x-reverse">
                                                @if ($material->status === \App\Enums\MaterialStatus::Completed)
                                                    <form method="POST" action="{{ route('materials.generate-activity', $material) }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                                            {{ __('Generate Game / Activity') }}
                                                        </button>
                                                    </form>
                                                @endif
                                                <form method="POST" action="{{ route('materials.destroy', $material) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 dark:text-red-400 hover:underline" onclick="return confirm(@js(__('Delete this material?')))">
                                                        {{ __('Delete') }}
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h3 class="text-lg font-medium mb-4">{{ __('Generated activities') }}</h3>

                    @if ($activities->isEmpty())
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('No activities generated yet for this child.') }}</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Title') }}</th>
                                        <th class="px-4 py-2 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Material') }}</th>
                                        <th class="px-4 py-2 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                                        <th class="px-4 py-2 text-start text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('XP') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($activities as $activity)
                                        <tr>
                                            <td class="px-4 py-3 text-sm">{{ $activity->title }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                {{ $activity->parentMaterial?->title ?? '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                @if ($activity->status === \App\Enums\ActivityStatus::Published)
                                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200">
                                                        {{ __('Ready to play') }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                                        {{ ucfirst($activity->status->value) }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">
                                                    {{ $activity->xp_reward }} XP
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <x-modal name="upload-material" :show="$errors->any()" focusable>
        <form method="POST" action="{{ route('materials.store') }}" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Upload Material') }}
            </h2>

            <div>
                <x-input-label for="title" :value="__('Title')" />
                <x-text-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title')" required autofocus />
                <x-input-error :messages="$errors->get('title')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="type" :value="__('Type')" />
                <select id="type" name="type" required class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full">
                    <option value="exam" @selected(old('type') === 'exam')>{{ __('Exam') }}</option>
                    <option value="summary" @selected(old('type') === 'summary')>{{ __('Summary') }}</option>
                    <option value="worksheet" @selected(old('type') === 'worksheet')>{{ __('Worksheet') }}</option>
                </select>
                <x-input-error :messages="$errors->get('type')" class="mt-2" />
            </div>

            <div
                x-data="{
                    fileName: '',
                    dragging: false,
                    onFileSelected(event) {
                        const file = event.target.files[0];
                        this.fileName = file ? file.name : '';
                    },
                    onDrop(event) {
                        this.dragging = false;
                        const input = this.$refs.fileInput;
                        if (event.dataTransfer.files.length) {
                            input.files = event.dataTransfer.files;
                            this.fileName = event.dataTransfer.files[0].name;
                        }
                    }
                }"
            >
                <x-input-label for="file" :value="__('PDF file')" />
                <div
                    class="mt-1 flex flex-col items-center justify-center rounded-md border-2 border-dashed px-6 py-8 transition"
                    :class="dragging ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-300 dark:border-gray-600'"
                    x-on:dragover.prevent="dragging = true"
                    x-on:dragleave.prevent="dragging = false"
                    x-on:drop.prevent="onDrop($event)"
                >
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('Drag and drop a PDF here, or click to browse') }}</p>
                    <p class="mt-2 text-sm font-medium text-indigo-600 dark:text-indigo-400" x-show="fileName" x-text="fileName"></p>
                    <input
                        x-ref="fileInput"
                        id="file"
                        name="file"
                        type="file"
                        accept="application/pdf,.pdf"
                        required
                        class="mt-4 block w-full text-sm text-gray-600 dark:text-gray-400 file:mr-4 file:rounded-md file:border-0 file:bg-gray-800 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-gray-700 dark:file:bg-gray-200 dark:file:text-gray-800"
                        x-on:change="onFileSelected($event)"
                    />
                </div>
                <x-input-error :messages="$errors->get('file')" class="mt-2" />
            </div>

            <div class="flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancel') }}
                </x-secondary-button>
                <x-primary-button>
                    {{ __('Upload') }}
                </x-primary-button>
            </div>
        </form>
    </x-modal>

    <x-modal name="create-goal" focusable>
        <form method="POST" action="{{ route('parent.goals.store') }}" class="p-6 space-y-6">
            @csrf

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('إضافة هدف دراسي جديد') }}
            </h2>

            <input type="hidden" name="student_id" value="{{ $activeStudent->id ?? '' }}">

            <div>
                <x-input-label for="subject_id" :value="__('Subject (optional)')" />
                <select id="subject_id" name="subject_id" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full">
                    <option value="">{{ __('General goal') }}</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected(old('subject_id') == $subject->id)>{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="target_activity_count" :value="__('Target activities')" />
                    <x-text-input id="target_activity_count" class="block mt-1 w-full" type="number" min="1" name="target_activity_count" :value="old('target_activity_count', 5)" required />
                </div>
                <div>
                    <x-input-label for="target_xp" :value="__('Target XP')" />
                    <x-text-input id="target_xp" class="block mt-1 w-full" type="number" min="0" name="target_xp" :value="old('target_xp', 200)" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="start_date" :value="__('Start date')" />
                    <x-text-input id="start_date" class="block mt-1 w-full" type="date" name="start_date" :value="old('start_date', now()->toDateString())" required />
                </div>
                <div>
                    <x-input-label for="end_date" :value="__('End date')" />
                    <x-text-input id="end_date" class="block mt-1 w-full" type="date" name="end_date" :value="old('end_date', now()->addDays(7)->toDateString())" required />
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">{{ __('Cancel') }}</x-secondary-button>
                <x-primary-button>{{ __('Save Goal') }}</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
