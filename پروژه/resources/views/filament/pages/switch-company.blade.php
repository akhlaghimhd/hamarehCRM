<x-filament-panels::page>
    <form wire:submit="switch" class="space-y-6">
        {{ $this->form }}

        <x-filament::button type="submit">
            اعمال شرکت جاری
        </x-filament::button>
    </form>
</x-filament-panels::page>
