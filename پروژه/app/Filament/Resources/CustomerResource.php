<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use App\Support\Fa;
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
use Filament\Schemas\Components\Section;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
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

    protected static string|UnitEnum|null $navigationGroup = 'فروش (جدید)';

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
            ->columns(1)
            ->components([
                Section::make('اطلاعات اصلی')
                    ->description('نوع و مشخصات هویتی مشتری را ثبت کنید.')
                    ->icon('heroicon-o-identification')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        Select::make('type')
                            ->label('نوع مشتری')
                            ->options([
                                Customer::TYPE_INDIVIDUAL => 'حقیقی',
                                Customer::TYPE_ORGANIZATION => 'حقوقی',
                            ])
                            ->required()
                            ->live()
                            ->native(false),
                        TextInput::make('name')
                            ->label('نام / نام شرکت')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['default' => 1, 'md' => 2]),
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
                        Toggle::make('is_active')
                            ->label('مشتری فعال')
                            ->default(true),
                    ]),
                Section::make('راه‌های ارتباطی')
                    ->description('اطلاعات تماس و نشانی مشتری.')
                    ->icon('heroicon-o-phone')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextInput::make('phone')
                            ->label('تلفن')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('email')
                            ->label('ایمیل')
                            ->email()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->label('نشانی')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('یادداشت داخلی')
                    ->description('توضیحات تکمیلی برای استفاده تیم.')
                    ->icon('heroicon-o-document-text')
                    ->collapsed()
                    ->schema([
                        Textarea::make('notes')
                            ->label('یادداشت')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('مشتری')
                    ->description(fn (Customer $record): ?string => $record->phone ?: $record->email)
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->wrap(),
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع')
                    ->formatStateUsing(fn (string $state): string => $state === Customer::TYPE_ORGANIZATION ? 'حقوقی' : 'حقیقی')
                    ->badge()
                    ->color(fn (string $state): string => $state === Customer::TYPE_ORGANIZATION ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('تلفن')
                    ->searchable()
                    ->visibleFrom('md'),
                Tables\Columns\TextColumn::make('email')
                    ->label('ایمیل')
                    ->searchable()
                    ->toggleable()
                    ->visibleFrom('lg'),
                Tables\Columns\TextColumn::make('national_id')
                    ->label('شناسه')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('lg'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->formatStateUsing(fn ($state): string => Fa::dateTime($state, 'Y/m/d'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('lg'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('وضعیت')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('نوع مشتری')
                    ->options([
                        Customer::TYPE_INDIVIDUAL => 'حقیقی',
                        Customer::TYPE_ORGANIZATION => 'حقوقی',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('وضعیت مشتری')
                    ->placeholder('همه مشتریان')
                    ->trueLabel('فعال')
                    ->falseLabel('غیرفعال'),
                TrashedFilter::make()
                    ->label('وضعیت حذف'),
            ])
            ->recordUrl(null)
            ->recordAction(null)
            ->defaultSort('id', 'desc')
            ->paginated([10, 15, 25, 50, 100])
            ->defaultPaginationPageOption(15)
            ->striped()
            ->emptyStateHeading('هنوز مشتری‌ای ثبت نشده است')
            ->emptyStateDescription('با ثبت اولین مشتری، ارتباطات و فرصت‌های فروش را در یک جا مدیریت کنید.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateActions([
                CreateAction::make()
                    ->label('ثبت اولین مشتری')
                    ->visible(fn () => static::canCreate()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('مشاهده')
                    ->icon('heroicon-o-eye')
                    ->visible(fn ($record) => static::canView($record)),
                EditAction::make()
                    ->label('ویرایش')
                    ->icon('heroicon-o-pencil-square')
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
