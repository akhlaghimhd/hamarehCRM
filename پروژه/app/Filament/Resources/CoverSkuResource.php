<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\CoverSkuResource\Pages;
use App\Filament\Resources\CoverSkuResource\RelationManagers\StocksRelationManager;
use App\Models\CoverMaterial;
use App\Models\CoverSku;
use App\Models\CoverStructure;
use App\Models\ProductLine;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use App\Support\Fa;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;

class CoverSkuResource extends Resource
{
    use ChecksResourcePermission;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }


    protected static ?string $permissionView = 'products.view';
    protected static ?string $permissionManage = 'products.manage';
    protected static ?string $model = CoverSku::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationLabel = 'جلد (SKU)';
    protected static ?string $modelLabel = 'جلد';
    protected static ?string $pluralModelLabel = 'جلدها';
    protected static string|UnitEnum|null $navigationGroup = 'نوتوپیا';
    protected static ?int $navigationSort = 13;

    

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();
        $id = ProductLine::query()->where('code', 'notopia')->value('id');
        if ($id) {
            $q->where('product_line_id', $id);
        }

        return $q;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('sku')->label('کد SKU')->required()->maxLength(50),
            TextInput::make('name')->label('نام نمایشی')->required()->maxLength(150),
            Select::make('series_type')->label('سری')->options([
                'stock' => 'دپو (stock)',
                'custom' => 'سفارشی (custom)',
            ])->required()->default('custom'),
            Select::make('hardness')->label('نوع جلد')->options([
                'soft' => 'نرم',
                'hard' => 'سخت',
            ])->required(),
            Select::make('cover_material_id')->label('جنس')
                ->options(fn () => CoverMaterial::query()->where('is_active', true)->orderBy('sort')->pluck('name', 'id'))
                ->searchable()->required(),
            Select::make('cover_structure_id')->label('مدل ساخت')
                ->options(fn () => CoverStructure::query()->where('is_active', true)->orderBy('sort')->pluck('name', 'id'))
                ->searchable()->required()
                ->helperText('دوتکه = امکان دو رنگ در موجودی'),
            Toggle::make('is_active')->label('فعال')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->weight('medium')
                    ->description(fn ($record) => $record?->sku)
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
                    ->color('primary'),
                Tables\Columns\TextColumn::make('series_type')
                    ->label('سری')
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'stock' ? 'دپو' : ($state === 'custom' ? 'سفارشی' : ($state ?: '—'))),
                Tables\Columns\TextColumn::make('hardness')
                    ->label('نوع')
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'soft' => 'نرم',
                        'hard' => 'سخت',
                        default => $state ?: '—',
                    }),
                Tables\Columns\TextColumn::make('coverMaterial.name')
                    ->label('جنس')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('coverStructure.name')
                    ->label('مدل ساخت')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('stocks_sum_stock_qty')
                    ->sum('stocks', 'stock_qty')
                    ->label('جمع موجودی')
                    ->sortable()
                    ->toggleable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => Fa::digits($state ?? 0)),
                IconColumn::make('is_active')
                    ->label('وضعیت')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-no-symbol')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable()
                    ->toggleable()
                    ->tooltip(fn ($record) => $record->is_active
                        ? 'وضعیت فعلی: فعال — برای غیرفعال کردن کلیک کنید'
                        : 'وضعیت فعلی: غیرفعال — برای فعال کردن کلیک کنید')
                    ->action(
                        \Filament\Actions\Action::make('toggleActive')
                            ->requiresConfirmation()
                            ->modalHeading(fn ($record) => $record->is_active ? 'غیرفعال کردن SKU جلد' : 'فعال کردن SKU جلد')
                            ->modalDescription('یادآوری: پس از راه‌اندازی ماژول سفارش، اگر این مورد در سفارش باز باشد تغییر وضعیت مسدود می‌شود. فعلاً فقط تأیید دستی است.')
                            ->modalSubmitActionLabel('تأیید')
                            ->action(function ($record) {
                                $record->update(['is_active' => ! $record->is_active]);
                            })
                    ),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخرین تغییر')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => Fa::dateTime($state)),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('حذف شده در')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => Fa::dateTime($state))
                    ->placeholder('—'),
            ])
            ->defaultSort('sku')
            ->striped()
            ->paginated([10, 15, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->filters([
                TrashedFilter::make()->label('حذف‌شده‌ها'),
                TernaryFilter::make('is_active')
                    ->label('وضعیت فعال')
                    ->trueLabel('فقط فعال')
                    ->falseLabel('فقط غیرفعال')
                    ->placeholder('همه'),
            ])
            ->emptyStateHeading('SKU جلدی ثبت نشده')
            ->emptyStateDescription('کدهای جلد (دپو / سفارشی) را اینجا تعریف کنید.')
            ->emptyStateActions([
                \Filament\Actions\CreateAction::make()
                    ->label('ثبت SKU جدید')
                    ->icon('heroicon-o-plus'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('ویرایش')
                    ->visible(fn ($record) => static::canEdit($record) && ! $record->trashed()),
                RestoreAction::make()
                    ->label('بازگردانی')
                    ->visible(fn ($record) => $record->trashed()),
                ForceDeleteAction::make()
                    ->label('حذف دائم')
                    ->visible(fn ($record) => $record->trashed()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف (قابل بازگشت)'),
                    RestoreBulkAction::make()->label('بازگردانی'),
                    ForceDeleteBulkAction::make()->label('حذف دائم'),
                    BulkAction::make('activate')
                        ->label('فعال‌سازی')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->modalHeading('فعال‌سازی موارد انتخاب‌شده')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label('غیرفعال‌سازی')
                        ->icon('heroicon-o-no-symbol')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('غیرفعال‌سازی موارد انتخاب‌شده')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            StocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoverSkus::route('/'),
            'create' => Pages\CreateCoverSku::route('/create'),
            'edit' => Pages\EditCoverSku::route('/{record}/edit'),
        ];
    }
}
