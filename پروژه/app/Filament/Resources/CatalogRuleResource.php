<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\CatalogRuleResource\Pages;
use App\Models\CatalogRule;
use App\Models\ProductLine;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;
use App\Support\Fa;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class CatalogRuleResource extends Resource
{
public static function shouldRegisterNavigation(): bool
    {
        return true;
    }


    use ChecksResourcePermission;
protected static ?string $permissionView = 'products.view';
    protected static ?string $permissionManage = 'products.manage';
    protected static ?string $model = CatalogRule::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationLabel = 'قواعد کاتالوگ';

    protected static string|\UnitEnum|null $navigationGroup = 'تنظیمات کاتالوگ';
    protected static ?string $modelLabel = 'قاعده';
    protected static ?string $pluralModelLabel = 'قواعد';
    protected static ?int $navigationSort = 22;

    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $notopiaId = \App\Models\ProductLine::query()->where('code', 'notopia')->value('id');
        if ($notopiaId && in_array('product_line_id', (new (static::getModel()))->getFillable(), true)) {
            $query->where('product_line_id', $notopiaId);
        }
        return $query;
    }


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_line_id')->label('خط محصول')
                ->options(fn () => ProductLine::query()->pluck('name', 'id')),
            TextInput::make('name')->label('نام قاعده')->required(),
            Select::make('scope')->label('محدوده')->options([
                'size_cover' => 'سایز ↔ جلد',
                'model_addon' => 'مدل ↔ اقلام',
                'general' => 'عمومی',
            ])->required(),
            Select::make('level')->label('سطح')->options([
                'required' => 'اجباری',
                'allowed' => 'مجاز',
                'discouraged' => 'غیرمعمول (هشدار)',
                'forbidden' => 'ممنوع',
            ])->required(),
            KeyValue::make('condition_json')->label('شرط (کلید/مقدار)')->columnSpanFull(),
            KeyValue::make('effect_json')->label('اثر (کلید/مقدار)')->columnSpanFull(),
            Textarea::make('message')->label('پیام راهنما')->rows(2)->columnSpanFull(),
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
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
                    ->color('primary'),
                Tables\Columns\TextColumn::make('scope')
                    ->label('محدوده')
                    ->sortable()
                    ->toggleable()
                    ->badge(),
                Tables\Columns\TextColumn::make('level')
                    ->label('سطح')
                    ->sortable()
                    ->toggleable()
                    ->badge(),
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
                            ->modalHeading(fn ($record) => $record->is_active ? 'غیرفعال کردن قانون' : 'فعال کردن قانون')
                            ->modalDescription('یادآوری: پس از راه‌اندازی ماژول سفارش، در صورت نیاز قواعد سخت‌گیرانه‌تر اعمال می‌شود. فعلاً فقط تأیید دستی است.')
                            ->modalSubmitActionLabel('تأیید')
                            ->action(function ($record) {
                                $record->update(['is_active' => ! $record->is_active]);
                            })
                    ),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخرین تغییر')
                    ->formatStateUsing(fn ($state) => Fa::dateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->striped()
            ->paginated([10, 15, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('وضعیت فعال')
                    ->trueLabel('فقط فعال')
                    ->falseLabel('فقط غیرفعال')
                    ->placeholder('همه'),
            ])
            ->emptyStateHeading('قانونی ثبت نشده')
            ->emptyStateDescription('قوانین کاتالوگ نوتوپیا را اینجا تعریف کنید.')
            ->emptyStateActions([
                \Filament\Actions\CreateAction::make()
                    ->label('ثبت قانون جدید')
                    ->icon('heroicon-o-plus'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('ویرایش')
                    ->visible(fn ($record) => static::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCatalogRules::route('/'),
            'create' => Pages\CreateCatalogRule::route('/create'),
            'edit' => Pages\EditCatalogRule::route('/{record}/edit'),
        ];
    }
}
