<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class CustomerResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'customers.view';
    protected static ?string $permissionManage = 'customers.manage';

    protected static ?string $model = Customer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $modelLabel = 'مشتری';

    protected static ?string $pluralModelLabel = 'مشتریان';

    protected static ?string $navigationLabel = 'مشتریان';

    protected static string|UnitEnum|null $navigationGroup = 'فروش و مشتری';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'مشتری';
    }

    public static function getPluralModelLabel(): string
    {
        return 'مشتریان';
    }

    public static function getNavigationLabel(): string
    {
        return 'مشتریان';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('نوع')
                    ->options([
                        Customer::TYPE_INDIVIDUAL => 'حقیقی',
                        Customer::TYPE_ORGANIZATION => 'حقوقی',
                    ])
                    ->required()
                    ->live(),
                TextInput::make('name')
                    ->label('نام / نام شرکت')
                    ->required()
                    ->maxLength(255),
                TextInput::make('national_id')
                    ->label('کد ملی / شناسه ملی')
                    ->maxLength(50),
                TextInput::make('economic_code')
                    ->label('کد اقتصادی')
                    ->maxLength(50)
                    ->visible(fn ($get) => $get('type') === Customer::TYPE_ORGANIZATION),
                TextInput::make('registration_number')
                    ->label('شماره ثبت')
                    ->maxLength(50)
                    ->visible(fn ($get) => $get('type') === Customer::TYPE_ORGANIZATION),
                TextInput::make('phone')
                    ->label('تلفن')
                    ->tel()
                    ->maxLength(50),
                TextInput::make('email')
                    ->label('ایمیل')
                    ->email()
                    ->maxLength(255),
                Textarea::make('address')
                    ->label('آدرس')
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->label('یادداشت')
                    ->rows(2)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع')
                    ->formatStateUsing(fn (string $state): string => $state === Customer::TYPE_ORGANIZATION ? 'حقوقی' : 'حقیقی')
                    ->badge(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('تلفن')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('ایمیل')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('national_id')
                    ->label('شناسه')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
            ])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('مشتری‌ای یافت نشد')
            ->emptyStateDescription('اولین مشتری را ایجاد کنید.')
            ->recordActions([
                ViewAction::make()->label('مشاهده')
                    ->visible(fn ($record) => static::canView($record)),
                EditAction::make()->label('ویرایش')
                    ->visible(fn ($record) => static::canEdit($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف انتخاب‌شده‌ها')
                        ->visible(fn () => static::canDeleteAny()),
                ]),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
