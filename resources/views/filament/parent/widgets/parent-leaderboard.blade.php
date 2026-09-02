<x-filament-widgets::widget>
    <x-filament::section heading="ترتيب الأسبوع">
        @if ($rank === null)
            <p class="text-sm text-gray-500">لا يوجد ابن نشط.</p>
        @else
            <p class="mb-4 text-sm text-gray-700 dark:text-gray-200">
                ترتيب ابنك هذا الأسبوع: <strong>#{{ $rank }}</strong>
            </p>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-right text-gray-500">
                            <th class="px-3 py-2">#</th>
                            <th class="px-3 py-2">الطالب</th>
                            <th class="px-3 py-2">XP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($leaders as $index => $leader)
                            <tr class="border-t border-gray-200 dark:border-gray-700">
                                <td class="px-3 py-2">{{ $index + 1 }}</td>
                                <td class="px-3 py-2">{{ \Illuminate\Support\Str::mask($leader->name, '*', 2) }}</td>
                                <td class="px-3 py-2">{{ $leader->weekly_xp ?? $leader->total_xp }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
