<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Add child') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('students.store') }}" class="p-6 space-y-4 text-gray-900 dark:text-gray-100">
                    @csrf

                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="grade_level" :value="__('Grade level')" />
                        <x-text-input id="grade_level" class="block mt-1 w-full" type="number" name="grade_level" :value="old('grade_level')" required />
                        <x-input-error :messages="$errors->get('grade_level')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="school_term" :value="__('School term')" />
                        <x-text-input id="school_term" class="block mt-1 w-full" type="number" name="school_term" :value="old('school_term')" required />
                        <x-input-error :messages="$errors->get('school_term')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="avatar_path" :value="__('Avatar path')" />
                        <x-text-input id="avatar_path" class="block mt-1 w-full" type="text" name="avatar_path" :value="old('avatar_path')" />
                        <x-input-error :messages="$errors->get('avatar_path')" class="mt-2" />
                    </div>

                    <x-primary-button>
                        {{ __('Save child') }}
                    </x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
