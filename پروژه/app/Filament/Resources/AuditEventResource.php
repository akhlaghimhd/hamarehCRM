<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditEventResource\Pages;
use App\Models\AuditEvent;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AuditEventResource extends Resource
{
    protected static ?string $model = AuditEvent::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $modelLabel = 'رویداد ممیزی';

    protected static ?string $pluralModelLabel = 'رویدادهای ممیزی';

    protected static ?string $navigationLabel = 'گزارش ممیزی';

    protected static string|UnitEnum|null $navigationGroup = 'گزارش‌ها';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->is_active
            && $user->isCompanyAdmin()
            && $user->currentCompanyId() !== null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('actor')
            ->latest('occurred_at');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('occurred_at')
                    ->label('زمان')
                    ->jalaliDateTime('Y/m/d H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('actor.name')
                    ->label('کاربر')
                    ->placeholder('سیستم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('event')
                    ->label('رویداد')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'ایجاد',
                        'updated' => 'ویرایش',
                        'deleted' => 'حذف',
                        'membership_added' => 'افزودن عضو',
                        'membership_updated' => 'تغییر عضویت',
                        'profile_updated' => 'ویرایش پروفایل',
                        'account_status_changed' => 'تغییر وضعیت حساب',
                        'restored' => 'بازیابی',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('auditable_type')
                    ->label('بخش')
                    ->formatStateUsing(fn (?string $state): string => match ($state ? class_basename($state) : null) {
                        'Company' => 'شرکت',
                        'User' => 'کاربر',
                        'Role' => 'نقش',
                        'Customer' => 'مشتری',
                        'Opportunity' => 'فرصت',
                        'Quote' => 'پیش‌فاکتور',
                        'Order' => 'سفارش',
                        default => $state ? class_basename($state) : '—',
                    })
                    ->description(fn (AuditEvent $record): string => 'شناسه رکورد: '.$record->auditable_id)
                    ->searchable(),
                Tables\Columns\TextColumn::make('auditable_id')
                    ->label('شناسه رکورد')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('old_values')
                    ->label('قبل از تغییر')
                    ->formatStateUsing(fn ($state): string => $state
                        ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : '—')
                    ->wrap()
                    ->limit(100)
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('new_values')
                    ->label('بعد از تغییر')
                    ->formatStateUsing(fn ($state): string => $state
                        ? json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : '—')
                    ->wrap()
                    ->limit(100)
                    ->copyable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('actor_user_id')
                    ->label('کاربر')
                    ->options(fn (): array => User::query()
                        ->whereHas('companies', fn (Builder $query) => $query->where('companies.id', auth()->user()?->currentCompanyId() ?? 0))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable(),
                Filter::make('occurred_at')
                    ->label('بازه زمانی')
                    ->schema([
                        DatePicker::make('from')->label('از تاریخ')->native(false),
                        DatePicker::make('until')->label('تا تاریخ')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('occurred_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('occurred_at', '<=', $date))),
                SelectFilter::make('event')
                    ->label('نوع رویداد')
                    ->options([
                        'created' => 'ایجاد',
                        'updated' => 'ویرایش',
                        'deleted' => 'حذف',
                        'membership_added' => 'افزودن عضو',
                        'membership_updated' => 'تغییر عضویت',
                        'profile_updated' => 'ویرایش پروفایل',
                        'account_status_changed' => 'تغییر وضعیت حساب',
                        'restored' => 'بازیابی',
                    ]),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->stackedOnMobile()
            ->emptyStateHeading('هنوز رویدادی ثبت نشده است')
            ->emptyStateDescription('این گزارش تغییرات مهم انجام‌شده در شرکت جاری را بر اساس کاربر، نوع رویداد و زمان نمایش می‌دهد.')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditEvents::route('/'),
        ];
    }
}
