<?php

namespace App\Filament\Resources;

use App\Actions\Identity\CreateCompanyForUser;
use App\Actions\Identity\SetCompanyStatus;
use App\Actions\Identity\UpdateCompanyProfile;
use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use App\Models\CompanyFinancialIdentity;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Fa;
use App\Support\CompanyMembershipAccess;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
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
use Illuminate\Support\Facades\Hash;
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

        return $user->isPlatformOwner() || $hasMembership;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        if (! $user || ! $user->is_active) {
            return false;
        }

        return $user->isPlatformOwner();
    }

    public static function canView($record): bool
    {
        $user = auth()->user();

        return $record instanceof Company
            && $user instanceof User
            && ($user->isPlatformOwner() || app(CompanyMembershipAccess::class)->isMember($user, (int) $record->id));
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        return $record instanceof Company
            && $user instanceof User
            && ($user->isPlatformOwner() || app(CompanyMembershipAccess::class)->canManage($user, (int) $record->id));
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

        if ($user->isPlatformOwner()) {
            return parent::getEloquentQuery();
        }

        $companyIds = DB::table('company_user')
            ->where('user_id', $user->id)
            ->pluck('company_id');

        return parent::getEloquentQuery()->whereIn('companies.id', $companyIds);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'md' => 2])
                ->columnSpanFull()
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
                        ->formatStateUsing(fn ($state) => filled($state) ? Fa::digits($state) : null)
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Fa::toEnglishDigits($state) : null)
                        ->mutateStateForValidationUsing(fn ($state) => filled($state) ? Fa::toEnglishDigits($state) : null)
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
                        ->formatStateUsing(fn ($state) => filled($state) ? Fa::digits($state) : null)
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Fa::toEnglishDigits($state) : null)
                        ->mutateStateForValidationUsing(fn ($state) => filled($state) ? Fa::toEnglishDigits($state) : null)
                        ->maxLength(50)
                        ->validationMessages(['max' => 'شماره ثبت بیش از حد مجاز طولانی است.'])
                        ->columnSpan(1)
                        ->extraInputAttributes(['lang' => 'fa', 'dir' => 'rtl', 'inputmode' => 'numeric']),
                    TextInput::make('phone')
                        ->label('تلفن')
                        ->formatStateUsing(fn ($state) => filled($state) ? Fa::digits($state) : null)
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Fa::toEnglishDigits($state) : null)
                        ->mutateStateForValidationUsing(fn ($state) => filled($state) ? Fa::toEnglishDigits($state) : null)
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
                    TextInput::make('website')
                        ->label('وب‌سایت')
                        ->placeholder('https://example.com')
                        ->maxLength(255)
                        ->validationMessages(['max' => 'نشانی وب‌سایت نباید بیشتر از ۲۵۵ نویسه باشد.'])
                        ->columnSpanFull()
                        ->extraInputAttributes(['lang' => 'en', 'dir' => 'ltr', 'inputmode' => 'url', 'autocapitalize' => 'off', 'spellcheck' => 'false']),


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
            ->emptyStateDescription('شرکت‌هایی که مجاز به مشاهده آن‌ها هستید در این فهرست نمایش داده می‌شوند.')
            ->recordActions([
                \Filament\Actions\Action::make('assignManager')
                    ->label('تعیین مدیر شرکت')
                    ->icon('heroicon-o-user-plus')
                    ->modalHeading('تعیین مدیر شرکت')
                    ->modalDescription('یک کاربر موجود را انتخاب کنید یا حساب کاربری جدید بسازید. مدیر شرکت عضو مستقل همین شرکت خواهد بود.')
                    ->schema([
                        Select::make('mode')
                            ->label('روش تعیین مدیر')
                            ->options(['existing' => 'انتخاب کاربر موجود', 'new' => 'ایجاد کاربر جدید'])
                            ->default('existing')
                            ->live()
                            ->required(),
                        Select::make('user_id')
                            ->label('کاربر')
                            ->options(fn (Company $record): array => User::query()
                                ->where('is_active', true)
                                ->whereDoesntHave('companies', fn ($query) => $query->where('companies.id', $record->id))
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (User $user): array => [$user->id => $user->name.' — '.$user->username])
                                ->all())
                            ->searchable()
                            ->visible(fn (callable $get): bool => $get('mode') === 'existing')
                            ->required(fn (callable $get): bool => $get('mode') === 'existing'),
                        TextInput::make('name')->label('نام')->required(fn (callable $get): bool => $get('mode') === 'new')->visible(fn (callable $get): bool => $get('mode') === 'new'),
                        TextInput::make('username')->label('نام کاربری')->alphaDash()->maxLength(100)->required(fn (callable $get): bool => $get('mode') === 'new')->visible(fn (callable $get): bool => $get('mode') === 'new'),
                        TextInput::make('email')->label('ایمیل')->email()->required(fn (callable $get): bool => $get('mode') === 'new')->visible(fn (callable $get): bool => $get('mode') === 'new'),
                        TextInput::make('password')->label('گذرواژه')->password()->revealable()->minLength(8)->required(fn (callable $get): bool => $get('mode') === 'new')->visible(fn (callable $get): bool => $get('mode') === 'new'),
                        TextInput::make('password_confirmation')->label('تکرار گذرواژه')->password()->revealable()->same('password')->required(fn (callable $get): bool => $get('mode') === 'new')->visible(fn (callable $get): bool => $get('mode') === 'new'),
                    ])
                    ->action(function (array $data, Company $record): void {
                        $actor = auth()->user();
                        abort_unless($actor instanceof User && $actor->is_active, 403);
                        abort_unless($actor->isPlatformOwner() || app(CompanyMembershipAccess::class)->canManage($actor, (int) $record->id), 403);

                        DB::transaction(function () use ($data, $record, $actor): void {
                            $company = Company::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();
                            if (! $company->is_active) {
                                throw new \\Illuminate\\Auth\\Access\\AuthorizationException('شرکت غیرفعال است.');
                            }

                            if (($data['mode'] ?? 'existing') === 'new') {
                                $validated = \\Illuminate\\Support\\Facades\\Validator::make($data, [
                                    'name' => ['required', 'string', 'max:255'],
                                    'username' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:users,username'],
                                    'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                                    'password' => ['required', 'string', 'min:8', 'same:password_confirmation'],
                                    'password_confirmation' => ['required', 'string'],
                                ])->validate();
                                $user = User::query()->create([
                                    'name' => $validated['name'],
                                    'username' => $validated['username'],
                                    'email' => $validated['email'],
                                    'password' => $validated['password'],
                                    'is_active' => true,
                                ]);
                            } else {
                                $user = User::query()->whereKey($data['user_id'] ?? 0)->where('is_active', true)->lockForUpdate()->firstOrFail();
                            }

                            $role = Role::query()->firstOrCreate(
                                ['company_id' => $company->id, 'name' => 'admin'],
                                ['label' => 'مدیر شرکت', 'is_system' => true, 'inherit_permissions' => false],
                            );
                            if ($role->wasRecentlyCreated) {
                                $role->permissions()->sync(Permission::query()->pluck('id'));
                            }

                            $membership = DB::table('company_user')
                                ->where('company_id', $company->id)
                                ->where('user_id', $user->id)
                                ->lockForUpdate()
                                ->first();

                            if ($membership) {
                                DB::table('company_user')->where('id', $membership->id)->update(['role_id' => $role->id, 'updated_at' => now()]);
                                DB::table('company_user_role')->where('company_user_id', $membership->id)->delete();
                                DB::table('company_user_role')->insert(['company_user_id' => $membership->id, 'role_id' => $role->id, 'created_at' => now(), 'updated_at' => now()]);
                            } else {
                                $membershipId = DB::table('company_user')->insertGetId([
                                    'company_id' => $company->id,
                                    'user_id' => $user->id,
                                    'role_id' => $role->id,
                                    'manager_user_id' => null,
                                    'is_default' => ! DB::table('company_user')->where('user_id', $user->id)->where('is_default', true)->exists(),
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                                DB::table('company_user_role')->insert(['company_user_id' => $membershipId, 'role_id' => $role->id, 'created_at' => now(), 'updated_at' => now()]);
                            }

                            DB::table('audit_events')->insert([
                                'company_id' => $company->id,
                                'auditable_type' => $user->getMorphClass(),
                                'auditable_id' => $user->id,
                                'actor_user_id' => $actor->id,
                                'event' => 'company_manager_assigned',
                                'old_values' => json_encode([], JSON_THROW_ON_ERROR),
                                'new_values' => json_encode(['user_id' => $user->id, 'role_id' => $role->id], JSON_THROW_ON_ERROR),
                                'metadata' => json_encode(['source' => 'company_row_action'], JSON_THROW_ON_ERROR),
                                'occurred_at' => now(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        });
                    })
                    ->visible(fn (Company $record): bool => static::canEdit($record)),
                \Filament\Actions\Action::make('manageFinancial')
                    ->label('هویت مالی و حساب‌ها')
                    ->icon('heroicon-o-building-library')
                    ->modalHeading('مدیریت پروفایل مالی شرکت')
                    ->schema([
                        Select::make('type')
                            ->label('نوع پروفایل مالی')
                            ->options(['official' => 'رسمی', 'unofficial' => 'غیررسمی'])
                            ->default('official')
                            ->required(),
                    ])
                    ->action(function (array $data, Company $record) {
                        $actor = auth()->user();
                        abort_unless($actor instanceof User && $actor->is_active, 403);
                        abort_unless($actor->isPlatformOwner() || app(CompanyMembershipAccess::class)->canManage($actor, (int) $record->id), 403);
                        $identity = CompanyFinancialIdentity::query()->where('company_id', $record->id)->where('type', $data['type'])->firstOrFail();
                        return redirect(\\App\\Filament\\Resources\\CompanyFinancialIdentityResource::getUrl('edit', ['record' => $identity]));
                    })
                    ->visible(fn (Company $record): bool => static::canEdit($record)),
                ViewAction::make()
                    ->label('مشاهده')
                    ->slideOver()
                    ->slideOverPosition(SlideOverPosition::Start)
                    ->modalWidth(Width::Medium)
                    ->closeModalByClickingAway(true),
                EditAction::make()
                    ->label('ویرایش')
                    ->slideOver()
                    ->slideOverPosition(SlideOverPosition::Start)
                    ->modalWidth(Width::Medium)
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
