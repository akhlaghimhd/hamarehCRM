<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\CatalogSeasonResource\Pages;
use App\Models\CatalogSeason;
use App\Support\Fa;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CatalogSeasonResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'products.view';

    protected static ?string $permissionManage = 'products.manage';

    protected static ?string $model = CatalogSeason::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'نسخه کاتالوگ';

    protected static ?string $pluralModelLabel = 'نسخه‌های کاتالوگ';

    protected static ?string $navigationLabel = 'نسخه‌های کاتالوگ';

    protected static string|\UnitEnum|null $navigationGroup = 'تنظیمات کاتالوگ';

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')
                ->label('کد نسخه')
                ->required()
                ->maxLength(50)
                ->helperText('مثلاً 1405 — در کد مدل‌ها استفاده می‌شود'),
            TextInput::make('name')
                ->label('نام نسخه')
                ->required()
                ->maxLength(100)
                ->helperText('مثلاً کالکشن ۱۴۰۵'),
            TextInput::make('year_label')
                ->label('برچسب سال')
                ->maxLength(20)
                ->helperText('نمایشی؛ مثلاً ۱۴۰۵'),
            TextInput::make('sort')
                ->label('ترتیب')
                ->numeric()
                ->default(0),
            DatePicker::make('starts_on')
                ->label('شروع فروش')
                ->native(false),
            DatePicker::make('ends_on')
                ->label('پایان فروش')
                ->native(false),
            Toggle::make('is_active')
                ->label('نسخه فعال برای فروش')
                ->default(true)
                ->helperText('غیرفعال = در انتخاب مدل‌های سفارش دیده نمی‌شود'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نسخه')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn ($record) => $record?->code)
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
                    ->color('primary'),
                Tables\Columns\TextColumn::make('year_label')->label('سال')->placeholder('—'),
                Tables\Columns\TextColumn::make('starts_on')->label('شروع')->date()->placeholder('—'),
                Tables\Columns\TextColumn::make('ends_on')->label('پایان')->date()->placeholder('—'),
                Tables\Columns\TextColumn::make('models_count')
                    ->counts('models')
                    ->label('مدل‌ها')
                    ->formatStateUsing(fn ($state) => Fa::digits((string) $state)),
                IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-no-symbol')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->action(
                        \Filament\Actions\Action::make('toggleActive')
                            ->requiresConfirmation()
                            ->modalHeading(fn ($record) => $record->is_active ? 'غیرفعال کردن نسخه' : 'فعال کردن نسخه')
                            ->modalDescription('مدل‌های این نسخه در فروش فقط وقتی نسخه فعال باشد پیشنهاد می‌شوند.')
                            ->modalSubmitActionLabel('تأیید')
                            ->action(fn ($record) => $record->update(['is_active' => ! $record->is_active]))
                    ),
            ])
            ->defaultSort('sort')
            ->filters([TrashedFilter::make()])
            ->recordActions([
                EditAction::make()->label('مدیریت'),
                DeleteAction::make()->label('حذف'),
                RestoreAction::make()->label('بازیابی'),
                ForceDeleteAction::make()->label('حذف دائم'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('نسخه‌ای تعریف نشده')
            ->emptyStateDescription('هر کالکشن/سال یک نسخه کاتالوگ است (مثلاً ۱۴۰۵ و ۱۴۰۶).')
            ->emptyStateActions([
                \Filament\Actions\CreateAction::make()->label('نسخه جدید'),
            ])
            ->paginated([10, 15, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCatalogSeasons::route('/'),
            'create' => Pages\CreateCatalogSeason::route('/create'),
            'edit' => Pages\EditCatalogSeason::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
