<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\QuoteResource\Pages;
use App\Filament\Resources\QuoteResource\RelationManagers\QuoteLinesRelationManager;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Quote;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class QuoteResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'quotes.view';

    protected static ?string $permissionManage = 'quotes.manage';

    protected static ?string $model = Quote::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = 'پیش‌فاکتور';

    protected static ?string $pluralModelLabel = 'پیش‌فاکتورها';

    protected static ?string $navigationLabel = 'پیش‌فاکتورها';

    protected static string|UnitEnum|null $navigationGroup = 'فروش و مشتری';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('اطلاعات پیش‌فاکتور')
                ->schema([
                    TextInput::make('title')
                        ->label('عنوان')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Select::make('customer_id')
                        ->label('مشتری')
                        ->options(fn () => Customer::query()->where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('status')
                        ->label('وضعیت')
                        ->options(Quote::statusOptions())
                        ->default(Quote::STATUS_DRAFT)
                        ->required()
                        ->native(false)
                        ->disabled(fn (?Quote $record) => $record?->status === Quote::STATUS_CONVERTED),
                    DatePicker::make('valid_until')
                        ->label('اعتبار تا')
                        ->native(false)
                        ->displayFormat('Y/m/d'),
                    Select::make('owner_user_id')
                        ->label('مسئول')
                        ->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))
                        ->default(fn () => auth()->id())
                        ->searchable(),
                    TextInput::make('discount_amount')
                        ->label('تخفیف (ریال)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                    TextInput::make('tax_amount')
                        ->label('مالیات (ریال)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                    TextInput::make('number')
                        ->label('شماره')
                        ->disabled()
                        ->dehydrated(false)
                        ->visibleOn('edit'),
                    TextInput::make('total')
                        ->label('جمع کل')
                        ->disabled()
                        ->dehydrated(false)
                        ->visibleOn('edit'),
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
                    ->searchable()
                    ->limit(36)
                    ->description(fn ($record) => $record->customer?->name),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Quote::statusOptions()[$state] ?? ($state ?? '—'))
                    ->color(fn (?string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        'converted' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total')
                    ->label('جمع')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->label('اعتبار')
                    ->date('Y/m/d')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('مسئول')
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
                    ->options(Quote::statusOptions()),
            ])
            ->emptyStateHeading('پیش‌فاکتوری نیست')
            ->emptyStateDescription('از فرصت برنده یا ایجاد مستقیم شروع کنید.')
            ->recordActions([
                EditAction::make()->label('ویرایش'),
                Action::make('toOrder')
                    ->label('تبدیل به سفارش')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Quote $record) => static::canEdit($record)
                        && $record->status !== Quote::STATUS_CONVERTED
                        && $record->lines()->exists())
                    ->action(function (Quote $record) {
                        $order = Order::fromQuote($record);
                        Notification::make()
                            ->title('سفارش ساخته شد')
                            ->body($order->number)
                            ->success()
                            ->send();

                        return redirect(OrderResource::getUrl('edit', ['record' => $order]));
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            QuoteLinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotes::route('/'),
            'create' => Pages\CreateQuote::route('/create'),
            'edit' => Pages\EditQuote::route('/{record}/edit'),
        ];
    }
}
