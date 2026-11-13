<x-filament-panels::page>
    <div class="fi-sc-form" style="max-width: 52rem; margin-inline: auto;">
        <p style="margin: 0 0 1rem; color: #64748b; font-size: 0.9rem; line-height: 1.7;">
            فقط هویت و گام اول را بزنید. سیستم مشتری را پیدا/می‌سازد، عنوان را پیشنهاد می‌دهد،
            مرحله سرنخ را می‌گذارد و یک پیگیری ثبت می‌کند.
        </p>

        <form wire:submit="create">
            {{ $this->form }}

            <div style="display:flex; flex-wrap:wrap; gap:0.75rem; margin-top:1.25rem; align-items:center;">
                <x-filament::button type="submit" icon="heroicon-o-bolt" wire:loading.attr="disabled">
                    ثبت سریع
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    color="gray"
                    :href="\App\Filament\Resources\OpportunityResource::getUrl('create')"
                    outlined
                >
                    فرم کامل
                </x-filament::button>

                <span wire:loading wire:target="create" style="font-size:0.85rem; color:#64748b;">
                    در حال ثبت…
                </span>
            </div>
        </form>
    </div>
</x-filament-panels::page>
