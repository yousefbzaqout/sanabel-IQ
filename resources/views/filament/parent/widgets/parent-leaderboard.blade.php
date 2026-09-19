<x-filament-widgets::widget>
    <x-filament::section heading="ترتيب الأسبوع">
        <div data-parent-widget-responsive class="w-full max-w-full">
            @if ($rank === null)
                <p class="text-sm text-gray-600 dark:text-gray-300">لا يوجد ابن نشط.</p>
            @else
                <p class="mb-4 text-sm text-gray-700 dark:text-gray-200">
                    ترتيب ابنك هذا الأسبوع: <strong>#{{ $rank }}</strong>
                </p>

                {{-- Stacked list avoids horizontal overflow on small viewports --}}
                <ul class="flex flex-col gap-2 sm:hidden" role="list">
                    @foreach ($leaders as $index => $leader)
                        <li class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-3 text-sm dark:border-gray-700">
                            <span class="font-semibold text-gray-800 dark:text-gray-100">#{{ $index + 1 }}</span>
                            <span class="min-w-0 flex-1 truncate text-gray-700 dark:text-gray-200">
                                {{ $leader->name }}
                            </span>
                            <span class="shrink-0 font-medium text-violet-700 dark:text-violet-300">
                                {{ $leader->weekly_xp ?? $leader->total_xp }} XP
                            </span>
                        </li>
                    @endforeach
                </ul>

                <div class="hidden max-w-full sm:block">
                    <table class="w-full table-fixed text-sm">
                        <thead>
                            <tr class="text-right text-gray-600 dark:text-gray-300">
                                <th class="w-12 px-3 py-2">#</th>
                                <th class="px-3 py-2">الطالب</th>
                                <th class="w-24 px-3 py-2">XP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($leaders as $index => $leader)
                                <tr class="border-t border-gray-200 dark:border-gray-700">
                                    <td class="px-3 py-2">{{ $index + 1 }}</td>
                                    <td class="truncate px-3 py-2">{{ $leader->name }}</td>
                                    <td class="px-3 py-2">{{ $leader->weekly_xp ?? $leader->total_xp }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
