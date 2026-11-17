<?php

namespace App\Filament\Resources;

use App\Actions\Identity\CreateCompanyForUser;
use App\Actions\Identity\SetCompanyStatus;
use App\Actions\Identity\UpdateCompanyProfile;
use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use App\Models\User;
use App\Support\Fa;
use App\Support\CompanyMembershipAccess;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Support\Enums\SlideOverPosition;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use UnitEnum;

class CompanyResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'companies.manage';
    protected static ?string $permissionManage = 'companies.manage';
    protected static ?string $model = Company::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $modelLabel = 'شرکت';
    protected static ?string $pluralModelLabel = 'شرکت‌ها';
    protected static ?string $navigationLabel = 'شرکت‌ها';
    protected static string|UnitEnum|null $navigationGroup = 'مدیریت سازمان';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        if (! $user || ! $user->is_active) {
            return false;
        }

        $hasMembership = DB::table('company_user')->where('user_id', $user->id)->exists();

        return $hasMembership || static::canCreate() || static::allowed(static::$permissionView);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        if (! $user || ! $user->is_active) {
            return false;
        }

        return ! DB::table('company_user')->where('user_id', $user->id)->exists()
            || $user->isCompanyAdmin();
    }

    public static function canView($record): bool
    {
        return $record instanceof Company
            && app(CompanyMembershipAccess::class)->isMember(auth()->user(), (int) $record->id);
    }

    public static function canEdit($record): bool
    {
        return $record instanceof Company
            && app(CompanyMembershipAccess::class)->canManage(auth()->user(), (int) $record->id);
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user || ! $user->is_active) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        $companyIds = DB::table('company_user')
            ->where('user_id', $user->id)
            ->pluck('company_id');

        return parent::getEloquentQuery()->whereIn('companies.id', $companyIds);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('اطلاعات شرکت')
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    TextInput::make('name')
                        ->label('نام شرکت')
                        ->required()
                        ->maxLength(255)
                        ->validationMessages([
                            'required' => 'وارد کردن نام شرکت الزامی است.',
                            'max' => 'نام شرکت نباید بیشتر از ۲۵۵ نویسه باشد.',
                        ])
                        ->columnSpanFull()
                        ->extraInputAttributes(['lang' => 'fa', 'dir' => 'rtl'])
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                            if (filled($get('slug'))) {
                                return;
                            }

                            $slug = Str::slug((string) $state);
                            $set('slug', $slug !== '' ? $slug : 'company-'.Str::lower(Str::random(8)));
                        }),
                    TextInput::make('slug')
                        ->label('شناسه سیستمی')
                        ->required()
                        ->maxLength(255)
                        ->validationMessages([
                            'required' => 'شناسه سیستمی الزامی است.',
                            'max' => 'شناسه سیستمی بیش از حد مجاز طولانی است.',
                            'unique' => 'این شناسه سیستمی قبلاً ثبت شده است.',
                        ])
                        ->columnSpan(1)
                        ->extraInputAttributes([
                            'lang' => 'en',
                            'dir' => 'ltr',
                            'inputmode' => 'url',
                            'autocapitalize' => 'off',
                            'spellcheck' => 'false',
                        ])
                        ->rules(['regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'])
                        ->validationMessages(['regex' => 'شناسه سیستمی فقط شامل حروف انگلیسی کوچک، عدد و خط تیره است.'])
                        ->unique(ignoreRecord: true),
                    TextInput::make('national_id')
                        ->label('شناسه ملی')
                        ->maxLength(11)
                        ->rules(['nullable', 'regex:/^[0-9۰-۹٠-٩]{11}$/'])
                        ->validationMessages([
                            'regex' => 'شناسه ملی شرکت باید دقیقاً ۱۱ رقم باشد.',
                            'max' => 'شناسه ملی شرکت باید دقیقاً ۱۱ رقم باشد.',
                        ])
                        ->columnSpan(1)
                        ->extraInputAttributes(['lang' => 'fa', 'dir' => 'ltr', 'inputmode' => 'numeric']),
                    TextInput::make('registration_number')
                        ->label('شماره ثبت')
                        ->maxLength(50)
                        ->validationMessages(['max' => 'شماره ثبت بیش از حد مجاز طولانی است.'])
                        ->columnSpan(1)
                        ->extraInputAttributes(['lang' => 'fa', 'dir' => 'rtl', 'inputmode' => 'numeric']),
                    TextInput::make('phone')
                        ->label('تلفن')
                        ->tel()
                        ->maxLength(11)
                        ->rules(['nullable', 'regex:/^[0-9۰-۹٠-٩]{8,11}$/'])
                        ->validationMessages([
                            'regex' => 'شماره تلفن باید بین ۸ تا ۱۱ رقم باشد.',
                            'max' => 'شماره تلفن نباید بیشتر از ۱۱ رقم باشد.',
                        ])
                        ->columnSpan(1)
                        ->extraInputAttributes(['lang' => 'fa', 'dir' => 'ltr', 'inputmode' => 'tel']),
                    TextInput::make('email')
                        ->label('ایمیل')
                        ->email()
                        ->maxLength(255)
                        ->validationMessages([
                            'email' => 'قالب ایمیل معتبر نیست.',
                            'max' => 'ایمیل بیش از حد مجاز طولانی است.',
                        ])
                        ->columnSpanFull()
                        ->extraInputAttributes([
                            'lang' => 'en',
                            'dir' => 'ltr',
                            'inputmode' => 'email',
                            'autocapitalize' => 'off',
                            'spellcheck' => 'false',
                        ]),
                    Textarea::make('address')
                        ->label('نشانی')
                        ->rows(3)
                        ->maxLength(2000)
                        ->validationMessages(['max' => 'نشانی نباید بیشتر از ۲۰۰۰ نویسه باشد.'])
                        ->columnSpanFull()
                        ->extraInputAttributes(['lang' => 'fa', 'dir' => 'rtl']),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام شرکت')
                    ->description(fn (Company $record): string => (string) $record->slug)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('national_id')
                    ->label('شناسه ملی')
                    ->formatStateUsing(fn ($state): string => Fa::digits($state))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('national_id', 'like', '%'.Fa::toEnglishDigits($search).'%'))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('registration_number')
                    ->label('شماره ثبت')
                    ->formatStateUsing(fn ($state): string => Fa::digits($state))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('registration_number', 'like', '%'.Fa::toEnglishDigits($search).'%'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone')
                    ->label('تلفن')
                    ->formatStateUsing(fn ($state): string => Fa::digits($state))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('phone', 'like', '%'.Fa::toEnglishDigits($search).'%'))
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('ایمیل')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('شناسه سیستمی')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('address')
                    ->label('نشانی')
                    ->limit(40)
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ToggleColumn::make('is_active')
                    ->label('وضعیت')
                    ->sortable()
                    ->disabled(fn (Company $record): bool => ! static::canEdit($record))
                    ->updateStateUsing(function (Company $record, bool $state): bool {
                        $actor = auth()->user();
                        abort_unless($actor instanceof User, 403);

                        return app(SetCompanyStatus::class)->handle($record, $state, $actor);
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->formatStateUsing(fn ($state): string => Fa::dateTime($state, 'Y/m/d'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->stackedOnMobile()
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('وضعیت شرکت')
                    ->trueLabel('فعال')
                    ->falseLabel('غیرفعال'),
                Filter::make('name')->label('نام شرکت')->schema([TextInput::make('value')->label('نام شرکت')])->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null) ? $query->where('name', 'like', '%'.$data['value'].'%') : $query),
                Filter::make('national_id')->label('شناسه ملی')->schema([TextInput::make('value')->label('شناسه ملی')])->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null) ? $query->where('national_id', 'like', '%'.Fa::toEnglishDigits($data['value']).'%') : $query),
                Filter::make('phone')->label('تلفن')->schema([TextInput::make('value')->label('تلفن')])->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null) ? $query->where('phone', 'like', '%'.Fa::toEnglishDigits($data['value']).'%') : $query),
            ])
            ->filtersFormColumns(2)
            ->filtersFormWidth(Width::FourExtraLarge)
            ->filtersFormMaxHeight('min(70vh, 36rem)')
            ->recordUrl(null)
            ->emptyStateHeading('شرکتی برای نمایش وجود ندارد')
            ->emptyStateDescription('شرکت‌هایی که عضو آن‌ها هستید در این فهرست نمایش داده می‌شوند.')
            ->recordActions([
                ViewAction::make()
                    ->label('مشاهده')
                    ->slideOver()
                    ->slideOverPosition(SlideOverPosition::Start)
                    ->modalWidth(Width::TwoExtraLarge)
                    ->closeModalByClickingAway(false),
                EditAction::make()
                    ->label('ویرایش')
                    ->slideOver()
                    ->slideOverPosition(SlideOverPosition::Start)
                    ->modalWidth(Width::TwoExtraLarge)
                    ->closeModalByClickingAway(false)
                    ->closeModalByEscaping(false)
                    ->modalCloseButton(false)
                    ->modalCancelActionLabel('انصراف')
                    ->modalSubmitActionLabel('ذخیره تغییرات')
                    ->successNotificationTitle('اطلاعات شرکت ذخیره شد')
                    ->failureNotificationTitle('ذخیره اطلاعات شرکت انجام نشد')
                    ->failureNotificationBody('لطفاً اطلاعات شرکت را بررسی کنید و دوباره تلاش کنید.')
                    ->visible(fn (Company $record): bool => static::canEdit($record))
                    ->using(function (Company $record, array $data): Company {
                        $actor = auth()->user();
                        abort_unless($actor instanceof User, 403);

                        return app(UpdateCompanyProfile::class)->handle($record, $data, $actor);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCompanies::route('/')];
    }
}
