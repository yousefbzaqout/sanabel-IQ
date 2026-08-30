@props(['activeStudent', 'students'])

@php
    $label = $activeStudent->name.' — '.__('Grade').' '.$activeStudent->grade_level;
@endphp

<div class="relative">
    <x-dropdown align="right" width="w-64">
        <x-slot name="trigger">
            <button type="button" class="inline-flex items-center max-w-56 px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                <span class="truncate">{{ $label }}</span>
                <svg class="fill-current h-4 w-4 ms-1 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </x-slot>

        <x-slot name="content">
            @foreach ($students as $student)
                <form method="POST" action="{{ route('students.select', $student) }}">
                    @csrf
                    <button type="submit" class="w-full text-start px-4 py-2 text-sm leading-5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none {{ $student->id === $activeStudent->id ? 'font-semibold' : '' }}">
                        {{ $student->name }} — {{ __('Grade') }} {{ $student->grade_level }}
                    </button>
                </form>
            @endforeach
        </x-slot>
    </x-dropdown>
</div>
