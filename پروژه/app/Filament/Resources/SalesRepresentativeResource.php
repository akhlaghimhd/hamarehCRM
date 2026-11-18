<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\SalesRepresentativeResource\Pages;
use App\Models\Customer;
use App\Models\SalesRepresentative;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SalesRepresentativeResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'sales_network.view';

    protected static ?string $permissionManage = 'sales_network.manage';

    protected static ?string $model = SalesRepresentative::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $modelLabel = 'نماینده فروش';

    protected static ?string $pluralModelLabel = 'نمایندگان فروش';

    protected static ?string $navigationLabel = 'نمایندگان فروش';

    protected static string|UnitEnum|null $navigationGroup = 'فروش و سفارش';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        $companyId = auth()->user()?->currentCompanyId();

        return $schema->components([
            Section::make('پروفایل نماینده')
                ->description('داخلی = کاربر عضو شرکت؛ بیرونی = طرف تجاری (مشتری/Party) بدون الزام حساب ورود')
                ->schema([
                    Select::make('type')
                        ->label('نوع')
                        ->options(SalesRepresentative::typeOptions())
                        ->required()
                        ->live()
                        ->native(false),
                    TextInput::make('code')
                        ->label('کد')
                        ->maxLength(50),
                    TextInput::make('name')
                        ->label('نام نمایشی')
                        ->required()
                        ->maxLength(255),
                    Select::make('user_id')
                        ->label('کاربر داخلی')
                        ->options(fn () => User::query()
                            ->where('is_active', true)
                            ->whereHas('companies', fn (Builder $q) => $q->where('companies.id', $companyId ?? 0))
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->visible(fn (callable $get) => $get('type') === SalesRepresentative::TYPE_INTERNAL)
                        ->required(fn (callable $get) => $get('type') === SalesRepresentative::TYPE_INTERNAL),
                    Select::make('customer_id')
                        ->label('طرف تجاری (بیرونی)')
                        ->options(fn () => Customer::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->visible(fn (callable $get) => $get('type') === SalesRepresentative::TYPE_EXTERNAL),
                    Select::make('status')
                        ->label('وضعیت')
                        ->options(SalesRepresentative::statusOptions())
                        ->default(SalesRepresentative::STATUS_ACTIVE)
                        ->required()
                        ->native(false),
                    TextInput::make('mobile')->label('موبایل')->tel(),
                    TextInput::make('email')->label('ایمیل')->email(),
                    DatePicker::make('started_on')->label('شروع')->native(false)->displayFormat('Y/m/d'),
                    DatePicker::make('ended_on')->label('پایان')->native(false)->displayFormat('Y/m/d'),
                    Select::make('parent_id')
                        ->label('نماینده بالادست')
                        ->options(fn () => SalesRepresentative::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable(),
                    Textarea::make('notes')->label('یادداشت')->rows(3)->columnSpanFull(),
                ])
                ->columns(['default' => 1, 'md' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('کد')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('name')->label('نام')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع')
                    ->formatStateUsing(fn (string $state) => SalesRepresentative::typeOptions()[$state] ?? $state)
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->formatStateUsing(fn (string $state) => SalesRepresentative::statusOptions()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => $state === 'active' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('user.name')->label('کاربر')->toggleable(),
                Tables\Columns\TextColumn::make('customer.name')->label('طرف تجاری')->toggleable(),
                Tables\Columns\TextColumn::make('mobile')->label('موبایل')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label('نوع')->options(SalesRepresentative::typeOptions()),
                Tables\Filters\SelectFilter::make('status')->label('وضعیت')->options(SalesRepresentative::statusOptions()),
            ])
            ->actions([
                EditAction::make()->label('ویرایش'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesRepresentatives::route('/'),
            'create' => Pages\CreateSalesRepresentative::route('/create'),
            'edit' => Pages\EditSalesRepresentative::route('/{record}/edit'),
        ];
    }
}
