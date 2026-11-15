<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\ProductFamilyResource\Pages;
use App\Models\ProductFamily;
use App\Support\Fa;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
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

class ProductFamilyResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'products.view';

    protected static ?string $permissionManage = 'products.manage';

    protected static ?string $model = ProductFamily::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $modelLabel = 'خانواده مدل';

    protected static ?string $pluralModelLabel = 'خانواده‌های مدل';

    protected static ?string $navigationLabel = 'خانواده مدل';

    protected static string|\UnitEnum|null $navigationGroup = 'نوتوپیا';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label('کد')->required()->maxLength(50),
            TextInput::make('name')->label('نام')->required()->maxLength(100),
            TextInput::make('sort')->label('ترتیب')->numeric()->default(0),
            Toggle::make('is_active')->label('فعال')->default(true),
            Textarea::make('description')->label('توضیحات')->rows(3)->columnSpanFull(),
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
                    ->weight('medium')
                    ->description(fn ($record) => $record?->code)
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
                    ->color('primary'),
                Tables\Columns\TextColumn::make('sort')
                    ->label('ترتیب')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => Fa::digits((string) $state)),
                Tables\Columns\TextColumn::make('models_count')
                    ->counts('models')
                    ->label('مدل‌ها')
                    ->formatStateUsing(fn ($state) => Fa::digits((string) $state)),
                IconColumn::make('is_active')
                    ->label('وضعیت')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-no-symbol')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable()
                    ->action(
                        \Filament\Actions\Action::make('toggleActive')
                            ->requiresConfirmation()
                            ->modalHeading(fn ($record) => $record->is_active ? 'غیرفعال کردن' : 'فعال کردن')
                            ->modalDescription('یادآوری: پس از راه‌اندازی ماژول سفارش، وابستگی به سفارش باز بررسی می‌شود.')
                            ->modalSubmitActionLabel('تأیید')
                            ->action(fn ($record) => $record->update(['is_active' => ! $record->is_active]))
                    ),
            ])
            ->defaultSort('sort')
            ->filters([TrashedFilter::make()])
            ->recordActions([
                EditAction::make()->label('ویرایش'),
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
            ->emptyStateHeading('خانواده‌ای تعریف نشده')
            ->emptyStateDescription('مثلاً کیوان، مانا')
            ->emptyStateActions([
                \Filament\Actions\CreateAction::make()->label('خانواده جدید'),
            ])
            ->paginated([10, 15, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductFamilies::route('/'),
            'create' => Pages\CreateProductFamily::route('/create'),
            'edit' => Pages\EditProductFamily::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
