<?php

namespace App\Filament\Resources\CatalogSeasonResource\Pages;

use App\Filament\Resources\CatalogSeasonResource;
use App\Models\CatalogSeason;
use App\Models\ProductModel;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditCatalogSeason extends EditRecord
{
    protected static string $resource = CatalogSeasonResource::class;

    protected static ?string $title = 'ویرایش نسخه کاتالوگ';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importModels')
                ->label('کپی مدل‌ها از نسخه دیگر')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn () => CatalogSeasonResource::canEdit($this->record))
                ->form([
                    Select::make('source_season_id')
                        ->label('نسخه مبدأ')
                        ->options(fn () => CatalogSeason::query()
                            ->whereKeyNot($this->record->id)
                            ->orderBy('sort')
                            ->pluck('name', 'id'))
                        ->required()
                        ->native(false)
                        ->helperText('مدل‌های فعال این نسخه با ماتریس مجاز به این کالکشن کپی می‌شوند.'),
                    Toggle::make('only_published')
                        ->label('فقط مدل‌های منتشرشده')
                        ->default(true),
                ])
                ->requiresConfirmation()
                ->modalHeading('کپی مدل‌ها به این نسخه')
                ->modalDescription('هر مدل با وضعیت پیش‌نویس در این کالکشن ساخته می‌شود؛ کد و نام قابل ویرایش بعدی است.')
                ->action(function (array $data): void {
                    $sourceId = (int) $data['source_season_id'];
                    $onlyPublished = (bool) ($data['only_published'] ?? true);

                    $query = ProductModel::query()
                        ->where('catalog_season_id', $sourceId)
                        ->where('is_active', true);

                    if ($onlyPublished) {
                        $query->where('status', 'published');
                    }

                    $models = $query->get();
                    if ($models->isEmpty()) {
                        Notification::make()
                            ->title('مدلی برای کپی نبود')
                            ->warning()
                            ->send();

                        return;
                    }

                    $count = 0;
                    DB::transaction(function () use ($models, &$count) {
                        foreach ($models as $model) {
                            $model->duplicateForCatalog(
                                seasonId: (int) $this->record->id,
                                code: null,
                                name: $model->name
                            );
                            $count++;
                        }
                    });

                    Notification::make()
                        ->title('کپی انجام شد')
                        ->body("{$count} مدل به «{$this->record->name}» منتقل شد (پیش‌نویس).")
                        ->success()
                        ->send();
                }),

            DeleteAction::make()->label('حذف'),
        ];
    }
}
