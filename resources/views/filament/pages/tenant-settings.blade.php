<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center justify-end gap-x-3 pt-4">
            <x-filament::button type="submit">
                حفظ التغييرات
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
