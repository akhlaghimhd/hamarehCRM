<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class OrderResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'orders.view';

    protected static ?string $permissionManage = 'orders.manage';

    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $modelLabel = 'سفارش';

    protected static ?string $pluralModelLabel = 'سفارش‌ها';

    protected static ?string $navigationLabel = 'سفارش‌ها';

    protected static string|UnitEnum|null $navigationGroup = 'فروش و مشتری';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('سفارش (snapshot قفل‌شده)')
                ->description('پس از تأیید، قیمت و پیکربندی خطوط تغییر نمی‌کند مگر لغو و صدور مجدد.')
                ->schema([
                    TextInput::make('number')
                        ->label('شماره')
                        ->disabled(),
                    TextInput::make('title')
                        ->label('عنوان')
                        ->disabled()
                        ->columnSpanFull(),
                    TextInput::make('customer.name')
                        ->label('مشتری')
                        ->disabled(),
                    Select::make('status')
                        ->label('وضعیت')
                        ->options(Order::statusOptions())
                        ->required()
                        ->native(false),
                    TextInput::make('subtotal')
                        ->label('جمع جزء')
                        ->disabled(),
                    TextInput::make('discount_amount')
                        ->label('تخفیف')
                        ->disabled(),
                    TextInput::make('tax_amount')
                        ->label('مالیات')
                        ->disabled(),
                    TextInput::make('total')
                        ->label('جمع کل')
                        ->disabled(),
                    Textarea::make('notes')
                        ->label('یادداشت')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(['default' => 1, 'md' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('شماره')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->limit(36)
                    ->description(fn ($record) => $record->customer?->name),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Order::statusOptions()[$state] ?? ($state ?? '—'))
                    ->color(fn (?string $state): string => match ($state) {
                        'draft' => 'gray',
                        'confirmed' => 'success',
                        'in_fulfillment' => 'info',
                        'completed' => 'primary',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->label('جمع')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('مسئول')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('confirmed_at')
                    ->label('تأیید')
                    ->dateTime('Y/m/d')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('به‌روزرسانی')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(Order::statusOptions()),
            ])
            ->emptyStateHeading('سفارشی نیست')
            ->recordActions([
                EditAction::make()->label('مشاهده / وضعیت'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
