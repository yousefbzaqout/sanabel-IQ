<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Set up your first child') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('onboarding.child.store') }}" class="p-6 sm:p-8 space-y-6 text-gray-900 dark:text-gray-100">
                    @csrf

                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ __('Tell us about your child so we can personalize activities for their grade and term.') }}
                    </p>

                    <div>
                        <x-input-label for="name" :value="__('Child name')" />
                        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="grade_level" :value="__('Grade')" />
                        <select id="grade_level" name="grade_level" required class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full">
                            @foreach (range(1, 6) as $grade)
                                <option value="{{ $grade }}" @selected((int) old('grade_level') === $grade)>
                                    {{ __('Grade') }} {{ $grade }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('grade_level')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="school_term" :value="__('School term')" />
                        <select id="school_term" name="school_term" required class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full">
                            @foreach (range(1, 2) as $term)
                                <option value="{{ $term }}" @selected((int) old('school_term', 1) === $term)>
                                    {{ __('Term') }} {{ $term }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('school_term')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="avatar_path" :value="__('Avatar path')" />
                        <x-text-input id="avatar_path" class="block mt-1 w-full" type="text" name="avatar_path" :value="old('avatar_path')" />
                        <x-input-error :messages="$errors->get('avatar_path')" class="mt-2" />
                    </div>

                    <x-primary-button>
                        {{ __('Continue to dashboard') }}
                    </x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
