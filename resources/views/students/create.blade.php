<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Add child') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/20 p-4 text-sm text-green-700 dark:text-green-300">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('students.store') }}" class="p-6 space-y-4 text-gray-900 dark:text-gray-100">
                    @csrf

                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus dir="auto" lang="ar" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="grade_level" :value="__('Grade level')" />
                        <select id="grade_level" name="grade_level" required class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm block mt-1 w-full">
                            @foreach (range(1, 6) as $grade)
                                <option value="{{ $grade }}" @selected((int) old('grade_level', 1) === $grade)>
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

                    <x-primary-button>
                        {{ __('Save child') }}
                    </x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
