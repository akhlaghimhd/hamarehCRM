<?php

namespace App\Filament\Resources;

use App\Actions\Identity\UpdateCompanyRole;
use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\RoleResource\Pages;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RoleResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'roles.manage';
    protected static ?string $permissionManage = 'roles.manage';

    protected static ?string $model = Role::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $modelLabel = 'نقش';

    protected static ?string $pluralModelLabel = 'نقش‌ها';

    protected static ?string $navigationLabel = 'نقش‌ها و مجوزها';

    protected static string|UnitEnum|null $navigationGroup = 'سازمان';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return 'نقش';
    }

    public static function getPluralModelLabel(): string
    {
        return 'نقش‌ها';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $companyId = auth()->user()?->currentCompanyId();

        if (! $companyId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('company_id', $companyId);
    }

    public static function canView($record): bool
    {
        return static::belongsToCurrentCompany($record)
            && static::allowed(static::$permissionView ?? null);
    }

    public static function canEdit($record): bool
    {
        if (! static::belongsToCurrentCompany($record)) {
            return false;
        }

        if ($record->name === 'admin') {
            $user = auth()->user();

            return $user !== null
                && (bool) ($user->is_active ?? false)
                && method_exists($user, 'isCompanyAdmin')
                && $user->isCompanyAdmin();
        }

        return static::allowed('roles.manage');
    }

    public static function canDelete($record): bool
    {
        return static::belongsToCurrentCompany($record)
            && ! $record->is_system
            && $record->name !== 'admin'
            && static::allowed('roles.manage');
    }

    protected static function belongsToCurrentCompany(mixed $record): bool
    {
        $companyId = auth()->user()?->currentCompanyId();

        return $record instanceof Role
            && $companyId !== null
            && (int) $record->company_id === (int) $companyId;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        $companyId = auth()->user()?->currentCompanyId();

        return $schema->components([
            Section::make('اطلاعات و ساختار نقش')
                ->description('نام، جایگاه سازمانی و نحوه ارث‌بری دسترسی‌ها را مشخص کنید.')
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    TextInput::make('name')->label('شناسه سیستمی')->required()->maxLength(100)
                        ->rules(fn (?Role $record): array => [
                            'regex:/^[a-z][a-z0-9_-]{0,99}$/',
                            ...(($record?->is_system || $record?->name === 'admin') ? [] : ['not_in:admin']),
                        ])
                        ->disabled(fn (?Role $record): bool => (bool) ($record?->is_system || $record?->name === 'admin'))
                        ->helperText('شناسه انگلیسی یکتا؛ admin رزرو شده است.'),
                    TextInput::make('label')->label('عنوان نقش')->required()->maxLength(100),
                    Select::make('parent_role_id')->label('نقش والد')
                        ->options(Role::query()->where('company_id', $companyId ?? 0)->where('name', '<>', 'admin')->orderBy('label')->pluck('label', 'id'))
                        ->searchable()->preload()->nullable()
                        ->helperText('نقش والد باید متعلق به همین شرکت باشد.'),
                    Toggle::make('inherit_permissions')->label('ارث‌بری مجوزهای نقش والد')->default(true)
                        ->helperText('با فعال‌سازی، مجوزهای مؤثر نقش والد نیز اعمال می‌شوند.'),
                    Select::make('copy_permissions_from_role_id')->label('الگوی کپی مجوزها')
                        ->options(Role::query()->where('company_id', $companyId ?? 0)->orderBy('label')->pluck('label', 'id'))
                        ->searchable()->nullable()
                        ->visible(fn (string $operation): bool => $operation === 'create')
                        ->helperText('فقط در زمان ایجاد یک کپی مستقل ساخته می‌شود.'),
                    Toggle::make('is_system')->label('نقش سیستمی')->default(false)->disabled()
                        ->helperText('این ویژگی فقط توسط کد و Seed رسمی مدیریت می‌شود.'),
                ]),
            Section::make('مجوزهای مستقیم')
                ->description('دسترسی‌ها را بر اساس نیاز این نقش انتخاب کنید. مجوزهای ارث‌رسیده از نقش والد جداگانه محاسبه می‌شوند.')
                ->schema([
                    CheckboxList::make('permissions')->label('دسترسی‌ها')
                        ->relationship('permissions', 'label', fn (Builder $query, ?Role $record): Builder => $record?->name === 'admin'
                            ? $query
                            : $query->whereNotIn('permissions.group', Permission::SYSTEM_GROUPS)
                                ->whereNotIn('permissions.name', Permission::SYSTEM_NAMES))
                        ->columns(['default' => 1, 'md' => 2, 'xl' => 3])
                        ->searchable()->bulkToggleable()
                        ->helperText('مجوزهای مرکزی سیستم فقط برای نقش مدیر سیستمی قابل تخصیص هستند.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label('عنوان')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('نام سیستمی')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('parentRole.label')
                    ->label('نقش والد')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('تعداد مجوز مستقیم')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('inherit_permissions')
                    ->label('ارث‌بری')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_system')
                    ->label('سیستمی')
                    ->boolean(),
            ])
            ->defaultSort('id')
            ->stackedOnMobile()
            ->filters([
                TernaryFilter::make('is_system')->label('نوع نقش')->trueLabel('سیستمی')->falseLabel('اختصاصی'),
                TernaryFilter::make('inherit_permissions')->label('ارث‌بری مجوزها')->trueLabel('فعال')->falseLabel('غیرفعال'),
            ])
            ->recordActions([
                EditAction::make()->slideOver()->using(function (Role $record, array $data): Role {
                    $actor = auth()->user();
                    abort_unless($actor instanceof User, 403);

                    return app(UpdateCompanyRole::class)->handle($record, $data, $actor);
                })->label('ویرایش'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
