<x-filament-panels::page>
    <form wire:submit="switch" class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 space-y-4 max-w-lg">
        <div>
            <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                <span class="text-sm font-medium text-gray-950 dark:text-white">شرکت جاری</span>
            </label>
            <select
                wire:model="company_id"
                class="fi-select-input mt-2 block w-full rounded-lg border-gray-300 shadow-sm dark:border-gray-600 dark:bg-gray-900"
            >
                @foreach ($companyOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <x-filament::button type="submit">
            اعمال شرکت جاری
        </x-filament::button>
    </form>
</x-filament-panels::page>
