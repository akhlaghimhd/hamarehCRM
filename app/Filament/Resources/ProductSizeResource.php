<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\ProductSizeResource\Pages;
use App\Models\ProductSize;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\BulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;

class ProductSizeResource extends Resource
{
public static function shouldRegisterNavigation(): bool
    {
        return true;
    }


    use ChecksResourcePermission;
protected static ?string $permissionView = 'products.view';
    protected static ?string $permissionManage = 'products.manage';
    protected static ?string $model = ProductSize::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-pointing-out';
    protected static ?string $modelLabel = 'سایز';
    protected static ?string $pluralModelLabel = 'سایزها';
    protected static ?string $navigationLabel = 'سایزها';

    protected static string|\UnitEnum|null $navigationGroup = 'نوتوپیا';
    protected static ?int $navigationSort = 2;

    
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
            TextInput::make('code')->label('کد')->required()->maxLength(50),
            TextInput::make('name')->label('نام')->required()->maxLength(100),
            Select::make('cover_type_constraint')->label('محدودیت نوع جلد')->options([
                'cardboard' => 'فقط مقوایی',
                'leather' => 'فقط چرمی',
            ])->placeholder('بدون محدودیت'),
            Toggle::make('is_active')->label('فعال')->default(true),
            TextInput::make('sort')->label('ترتیب')->numeric()->default(0),
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
                    ->description(fn ($record) => $record?->code)
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
                    ->color('primary'),
                Tables\Columns\TextColumn::make('cover_type_constraint')
                    ->label('محدودیت جلد')
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cardboard' => 'مقوایی',
                        'leather' => 'چرمی',
                        default => $state ?: '—',
                    }),
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
                            ->modalHeading(fn ($record) => $record->is_active ? 'غیرفعال کردن سایز' : 'فعال کردن سایز')
                            ->modalDescription('یادآوری: پس از راه‌اندازی ماژول سفارش، اگر این مورد در سفارش باز باشد تغییر وضعیت مسدود می‌شود. فعلاً فقط تأیید دستی است.')
                            ->modalSubmitActionLabel('تأیید')
                            ->action(function ($record) {
                                $record->update(['is_active' => ! $record->is_active]);
                            })
                    ),
                Tables\Columns\TextColumn::make('sort')
                    ->label('ترتیب')
                    ->sortable()
                    ->toggleable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => Fa::digits($state ?? 0)),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخرین تغییر')
                    ->formatStateUsing(fn ($state) => Fa::dateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('حذف شده در')
                    ->formatStateUsing(fn ($state) => Fa::dateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
            ])
            ->defaultSort('sort')
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
            ->emptyStateHeading('سایزی ثبت نشده')
            ->emptyStateDescription('سایزهای محصول نوتوپیا را اینجا تعریف کنید.')
            ->emptyStateActions([
                \Filament\Actions\CreateAction::make()
                    ->label('ثبت سایز جدید')
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
                        ->modalDescription('یادآوری: بعداً اگر مورد در سفارش باز باشد، فعال/غیرفعال مسدود می‌شود.')
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
            'index' => Pages\ListProductSizes::route('/'),
            'create' => Pages\CreateProductSize::route('/create'),
            'edit' => Pages\EditProductSize::route('/{record}/edit'),
        ];
    }
}
