<x-filament-panels::page>
    <form wire:submit="switch" class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 space-y-4 max-w-lg">
        <div class="space-y-2">
            <div class="text-sm font-medium text-gray-950 dark:text-white">شرکت جاری</div>

            @forelse ($companyOptions as $option)
                <label class="flex items-center gap-3 rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700 cursor-pointer">
                    <input
                        type="radio"
                        wire:model="company_id"
                        value="{{ $option['id'] }}"
                        class="h-4 w-4"
                    >
                    <span>{{ $option['name'] }}</span>
                </label>
            @empty
                <p class="text-sm text-danger-600">هیچ شرکتی به این کاربر وصل نیست.</p>
            @endforelse
        </div>

        <x-filament::button type="submit" :disabled="empty($companyOptions)">
            اعمال شرکت جاری
        </x-filament::button>
    </form>
</x-filament-panels::page>
