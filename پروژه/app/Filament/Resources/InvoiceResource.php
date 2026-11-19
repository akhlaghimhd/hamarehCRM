<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers\PaymentsRelationManager;
use App\Models\Invoice;
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

class InvoiceResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'invoices.view';

    protected static ?string $permissionManage = 'invoices.manage';

    protected static ?string $model = Invoice::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $modelLabel = 'فاکتور';

    protected static ?string $pluralModelLabel = 'فاکتورها';

    protected static ?string $navigationLabel = 'فاکتورها';

    protected static string|UnitEnum|null $navigationGroup = 'مالی عملیاتی';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('فاکتور')
                ->description('مبالغ از سفارش snapshot شده‌اند؛ هویت و حساب در زمان صدور قفل شده‌اند.')
                ->schema([
                    TextInput::make('number')->label('شماره')->disabled(),
                    TextInput::make('title')->label('عنوان')->disabled()->columnSpanFull(),
                    TextInput::make('customer.name')->label('مشتری')->disabled(),
                    TextInput::make('order.number')->label('سفارش')->disabled(),
                    Select::make('status')
                        ->label('وضعیت')
                        ->options(Invoice::statusOptions())
                        ->disabled()
                        ->dehydrated(false),
                    Select::make('identity_type')
                        ->label('هویت')
                        ->options(Invoice::identityTypeOptions())
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('total')->label('جمع کل')->disabled(),
                    TextInput::make('paid_amount')->label('پرداخت‌شده')->disabled(),
                    TextInput::make('currency')->label('ارز')->disabled(),
                    Textarea::make('notes')->label('یادداشت')->rows(3)->columnSpanFull(),
                ])
                ->columns(['default' => 1, 'md' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('شماره')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer.name')->label('مشتری')->searchable(),
                Tables\Columns\TextColumn::make('order.number')->label('سفارش')->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Invoice::statusOptions()[$state] ?? ($state ?? '—'))
                    ->color(fn (?string $state): string => match ($state) {
                        'issued' => 'info',
                        'partial' => 'warning',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('identity_type')
                    ->label('هویت')
                    ->formatStateUsing(fn (?string $state): string => Invoice::identityTypeOptions()[$state] ?? ($state ?? '—'))
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total')->label('جمع')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')->label('پرداخت‌شده')->numeric(),
                Tables\Columns\TextColumn::make('issued_on')->label('تاریخ')->jalaliDate('Y/m/d')->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('وضعیت')->options(Invoice::statusOptions()),
                Tables\Filters\SelectFilter::make('identity_type')->label('هویت')->options(Invoice::identityTypeOptions()),
            ])
            ->emptyStateHeading('فاکتوری نیست')
            ->recordActions([
                EditAction::make()->label('مدیریت'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
