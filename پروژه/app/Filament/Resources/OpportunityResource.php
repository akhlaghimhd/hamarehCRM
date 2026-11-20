<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\OpportunityResource\Pages;
use App\Filament\Resources\OpportunityResource\RelationManagers\ActivitiesRelationManager;
use App\Models\Activity;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\OpportunityStep;
use App\Models\SalesRepresentative;
use App\Models\User;
use App\Support\Fa;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OpportunityResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'opportunities.view';
    protected static ?string $permissionManage = 'opportunities.manage';
    protected static ?string $model = Opportunity::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-funnel';
    protected static ?string $modelLabel = 'فرصت';
    protected static ?string $pluralModelLabel = 'فرصت‌ها';
    protected static ?string $navigationLabel = 'فرصت‌ها';
    protected static string|UnitEnum|null $navigationGroup = 'بازاریابی';
    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        $isAdmin = fn () => auth()->user()?->isCompanyAdmin() ?? false;

        return $schema->columns(1)->components([
            Section::make('۱. سازمان و تماس')->icon('heroicon-o-building-office-2')->collapsible()->schema([
                TextInput::make('organization_name')->label('نام سازمان / شرکت')->maxLength(120)->rules(['required', 'max:120'])->markAsRequired()->columnSpan(['default' => 1, 'md' => 2]),
                Select::make('product_interest')->label('محصولات مورد علاقه')->options(Opportunity::productInterestOptions())->multiple()->searchable()->native(false)->columnSpan(['default' => 1, 'md' => 2]),
                Select::make('industry')->label('صنعت')->options(Opportunity::industryOptions())->native(false)->live(),
                TextInput::make('industry_other')->label('توضیح صنعت (سایر)')->visible(fn (Get $get) => $get('industry') === 'other')->maxLength(80),
                TextInput::make('contact_name')->label('نام فرد تماس')->maxLength(80),
                Select::make('contact_role')->label('نقش فرد در خرید')->options(Opportunity::decisionRoleOptions())->native(false),
                TextInput::make('mobile')->label('موبایل')->maxLength(15)->dehydrateStateUsing(function ($state) {
                    if ($state === null || $state === '') { return $state; }
                    return preg_replace('/[\s\-()]/', '', Fa::toEnglishDigits((string) $state)) ?? $state;
                }),
                TextInput::make('phone')->label('تلفن ثابت')->maxLength(11),
            ])->columns(['default' => 1, 'md' => 2])->columnSpanFull(),
            Section::make('۲. ایمیل و آدرس')->collapsed()->collapsible()->schema([
                TextInput::make('email')->label('ایمیل')->maxLength(120),
                TextInput::make('address')->label('آدرس / محل فعالیت')->maxLength(200),
            ])->columns(['default' => 1, 'md' => 2])->columnSpanFull(),
            Section::make('۳. نیاز، بودجه و ارزش')->collapsed()->collapsible()->schema([
                TextInput::make('purchase_use')->label('کاربرد خرید / نیاز')->maxLength(120),
                TextInput::make('qty_approx')->label('تیراژ تقریبی')->maxLength(40),
                TextInput::make('amount')->label('بودجه تقریبی (ریال)'),
                TextInput::make('buy_timing')->label('زمان خرید احتمالی')->maxLength(60),
                Select::make('value_focus')->label('ارزش مورد توجه مشتری')->options(Opportunity::valueFocusOptions())->native(false)->live(),
                TextInput::make('value_other')->label('توضیح ارزش (سایر)')->visible(fn (Get $get) => $get('value_focus') === 'other')->maxLength(80),
                Textarea::make('main_objection')->label('نگرانی یا اعتراض اصلی')->rows(2)->columnSpanFull(),
            ])->columns(['default' => 1, 'md' => 2])->columnSpanFull(),
            Section::make('۴. مرحله قیف و وضعیت')->collapsible()->schema([
                Select::make('stage_id')->label('مرحله قیف')->options(function () {
                    OpportunityStage::ensureDefaults();
                    return OpportunityStage::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id');
                })->native(false)->rules(['required'])->markAsRequired(),
                Select::make('status')->label('وضعیت فرصت')->options(Opportunity::statusOptions())->default(Opportunity::STATUS_ACTIVE)->native(false)->rules(['required'])->markAsRequired(),
                Select::make('owner_user_id')->label('مالک فرصت')->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))->default(fn () => auth()->id())->searchable()->visible($isAdmin)->dehydrated()->rules(['required'])->markAsRequired(),
                Select::make('sales_representative_id')->label('نماینده فروش')->options(fn () => SalesRepresentative::query()->where('status', 'active')->orderBy('name')->pluck('name', 'id'))->searchable()->nullable(),
                Select::make('followup_user_id')->label('مسئول پیگیری')->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))->default(fn () => auth()->id())->searchable()->visible($isAdmin)->dehydrated(),
                TextInput::make('title')->label('عنوان فرصت')->maxLength(160)->rules(['required'])->markAsRequired()->columnSpanFull(),
                Textarea::make('notes')->label('یادداشت')->rows(3)->columnSpanFull(),
            ])->columns(['default' => 1, 'md' => 2])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Opportunity $record) => static::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('code')->label('کد')
                    ->state(fn (Opportunity $r) => 'OPP-'.str_pad((string) $r->id, 3, '0', STR_PAD_LEFT))
                    ->formatStateUsing(fn ($state) => Fa::digits((string) $state))
                    ->sortable(query: fn (Builder $q, string $direction) => $q->orderBy('id', $direction))
                    ->weight('medium')->color('primary')->wrap(false),
                TextColumn::make('organization_name')->label('سازمان')->limit(22)
                    ->tooltip(fn (Opportunity $r) => $r->organization_name)
                    ->description(function (Opportunity $r): string {
                        $owner = $r->owner?->name ?? '—';
                        $follow = $r->followupUser?->name;
                        if ($follow && (int) $r->followup_user_id !== (int) $r->owner_user_id) {
                            return 'مالک: '.$owner.' · ارجاع به: '.$follow;
                        }
                        return 'مالک: '.$owner;
                    })->wrap(false),
                TextColumn::make('product')->label('محصول')
                    ->state(fn (Opportunity $r) => $r->productInterestLabel())->limit(12)
                    ->tooltip(fn (Opportunity $r) => $r->productInterestLabel())->wrap(false),
                TextColumn::make('status')->label('وضعیت')->badge()
                    ->formatStateUsing(fn (?string $state) => Opportunity::statusOptions()[$state] ?? $state)->wrap(false),
                TextColumn::make('stage.name')->label('مرحله')->limit(14)
                    ->tooltip(fn (Opportunity $r) => $r->stage?->name)->wrap(false),
                TextColumn::make('next_step')->label('گام بعدی')->limit(18)
                    ->tooltip(fn (Opportunity $r) => $r->next_step)->placeholder('—')->wrap(false),
                TextColumn::make('next_step_due')->label('تاریخ گام')
                    ->formatStateUsing(fn ($state) => $state ? Fa::dateTime($state, 'Y/m/d') : '—')->sortable()->wrap(false),
            ])
            ->defaultSort('updated_at', 'desc')
            ->paginated([10, 15, 25, 50])
            ->deferFilters(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(['default' => 1, 'sm' => 2, 'md' => 3, 'lg' => 5])
            ->filters([
                Filter::make('q')->form([
                    TextInput::make('q')->hiddenLabel()->placeholder('جستجوی سازمان، کد، مخاطب...'),
                ])->query(function (Builder $query, array $data): Builder {
                    $q = trim((string) ($data['q'] ?? ''));
                    if ($q === '') { return $query; }
                    $digits = preg_replace('/\D+/', '', Fa::toEnglishDigits($q)) ?? '';
                    return $query->where(function (Builder $inner) use ($q, $digits): void {
                        $inner->where('organization_name', 'like', '%'.$q.'%')
                            ->orWhere('contact_name', 'like', '%'.$q.'%')
                            ->orWhere('title', 'like', '%'.$q.'%')
                            ->orWhere('mobile', 'like', '%'.$q.'%');
                        if ($digits !== '') { $inner->orWhere('id', (int) $digits); }
                    });
                })->indicateUsing(fn (array $data): ?string => filled($data['q'] ?? null) ? 'جستجو: '.$data['q'] : null),
                Filter::make('status')->form([
                    Select::make('value')->hiddenLabel()->placeholder('همه وضعیت‌ها')->options(Opportunity::statusOptions())->native(false),
                ])->query(function (Builder $query, array $data): Builder {
                    $v = $data['value'] ?? null;
                    return filled($v) ? $query->where('status', $v) : $query;
                })->indicateUsing(fn (array $data): ?string => filled($data['value'] ?? null) ? (Opportunity::statusOptions()[$data['value']] ?? (string) $data['value']) : null),
                Filter::make('stage_id')->form([
                    Select::make('value')->hiddenLabel()->placeholder('همه مراحل')->options(fn () => OpportunityStage::query()->orderBy('sort_order')->pluck('name', 'id'))->native(false),
                ])->query(function (Builder $query, array $data): Builder {
                    $v = $data['value'] ?? null;
                    return filled($v) ? $query->where('stage_id', $v) : $query;
                })->indicateUsing(function (array $data): ?string {
                    if (! filled($data['value'] ?? null)) { return null; }
                    return OpportunityStage::query()->whereKey($data['value'])->value('name');
                }),
                Filter::make('referral')->form([
                    Select::make('value')->hiddenLabel()->placeholder('همه (ارجاع)')->options([
                        'to_me' => 'ارجاع‌شده به من',
                        'by_me' => 'ارجاع‌داده‌شده توسط من',
                        'open' => 'دارای ارجاع باز',
                    ])->native(false),
                ])->query(function (Builder $query, array $data): Builder {
                    $v = $data['value'] ?? null;
                    $uid = auth()->id();
                    if ($v === 'to_me') {
                        return $query->where('followup_user_id', $uid)->where(function (Builder $q) use ($uid): void {
                            $q->whereNull('owner_user_id')->orWhere('owner_user_id', '!=', $uid);
                        });
                    }
                    if ($v === 'by_me') {
                        return $query->where('owner_user_id', $uid)->whereNotNull('followup_user_id')->where('followup_user_id', '!=', $uid);
                    }
                    if ($v === 'open') {
                        return $query->whereNotNull('followup_user_id')->whereColumn('followup_user_id', '!=', 'owner_user_id');
                    }
                    return $query;
                })->indicateUsing(fn (array $data): ?string => match ($data['value'] ?? null) {
                    'to_me' => 'ارجاع‌شده به من',
                    'by_me' => 'ارجاع‌داده‌شده توسط من',
                    'open' => 'دارای ارجاع باز',
                    default => null,
                }),
                Filter::make('user')->form([
                    Select::make('value')->hiddenLabel()->placeholder('همه کاربران')->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))->searchable()->native(false),
                ])->query(function (Builder $query, array $data): Builder {
                    $uid = $data['value'] ?? null;
                    if (! $uid) { return $query; }
                    return $query->where(function (Builder $q) use ($uid): void {
                        $q->where('owner_user_id', $uid)->orWhere('followup_user_id', $uid);
                    });
                })->indicateUsing(function (array $data): ?string {
                    if (! filled($data['value'] ?? null)) { return null; }
                    return User::query()->whereKey($data['value'])->value('name');
                }),
            ])
            ->filtersTriggerAction(fn (Action $action) => $action->hidden())
            ->filtersResetAction(fn (Action $action) => $action->label('حذف فیلتر')->link()->size('sm')->color('gray'))
            ->persistFiltersInSession()
            ->searchable(false)
            ->emptyStateHeading('فرصتی ثبت نشده')
            ->emptyStateDescription('اولین فرصت را با ثبت سریع یا فرم کامل بسازید.')
            ->emptyStateActions([
                Action::make('quick')->label('ثبت سریع')->url(static::getUrl('quick-create'))->icon('heroicon-o-bolt'),
            ])
            ->recordActions([
                EditAction::make()->label('')->tooltip('ویرایش فرصت')->icon('heroicon-o-pencil-square')->iconButton(),
                Action::make('completeStep')->label('')->tooltip('تکمیل گام')->icon('heroicon-o-check-circle')->iconButton()->color('success')
                    ->visible(fn (Opportunity $r) => static::canEdit($r) && filled($r->next_step) && ! in_array($r->status, [Opportunity::STATUS_WON, Opportunity::STATUS_LOST], true))
                    ->modalHeading('تکمیل گام')
                    ->form([
                        Textarea::make('result')->label('نتیجه گام')->required()->rows(3),
                        Textarea::make('next_step')->label('گام بعدی (اختیاری)')->rows(2),
                        DatePicker::make('next_step_due')->label('مهلت گام بعدی')->native(false)->default(now()->addDays(3)),
                    ])
                    ->action(function (Opportunity $record, array $data): void {
                        $open = OpportunityStep::query()->where('opportunity_id', $record->id)->where('is_open', true)->orderByDesc('id')->first();
                        if ($open) { $open->close(trim((string) $data['result'])); }
                        Activity::query()->create([
                            'company_id' => $record->company_id, 'opportunity_id' => $record->id, 'opportunity_step_id' => $open?->id,
                            'user_id' => auth()->id(), 'type' => Activity::TYPE_NOTE, 'subject' => 'نتیجه گام', 'body' => $data['result'],
                            'due_at' => now(), 'is_done' => true, 'completed_at' => now(),
                        ]);
                        $next = trim((string) ($data['next_step'] ?? ''));
                        if ($next !== '') {
                            OpportunityStep::query()->create([
                                'company_id' => $record->company_id, 'opportunity_id' => $record->id,
                                'assigned_user_id' => $record->followup_user_id ?: auth()->id(), 'title' => $next,
                                'due_at' => $data['next_step_due'] ?? now()->addDays(3), 'is_open' => true,
                                'sort_order' => (int) OpportunityStep::query()->where('opportunity_id', $record->id)->max('sort_order') + 1,
                            ]);
                            $record->next_step = $next;
                            $record->next_step_due = $data['next_step_due'] ?? now()->addDays(3);
                        } else {
                            $record->next_step = null;
                            $record->next_step_due = null;
                        }
                        $record->save();
                        Notification::make()->title('گام تکمیل شد')->success()->send();
                    }),
                Action::make('logActivity')->label('')->tooltip('ثبت فعالیت')->icon('heroicon-o-clipboard-document-list')->iconButton()->color('gray')
                    ->visible(fn (Opportunity $r) => static::canEdit($r))
                    ->modalHeading('ثبت فعالیت')
                    ->form([
                        Select::make('type')->label('نوع')->options(Activity::typeOptions())->required()->native(false),
                        DatePicker::make('due_at')->label('تاریخ')->default(now())->required()->native(false),
                        Textarea::make('subject')->label('خلاصه')->required()->rows(2),
                        Textarea::make('body')->label('توضیح')->rows(2),
                    ])
                    ->action(function (Opportunity $record, array $data): void {
                        $open = OpportunityStep::query()->where('opportunity_id', $record->id)->where('is_open', true)->orderByDesc('id')->first();
                        Activity::query()->create([
                            'company_id' => $record->company_id, 'opportunity_id' => $record->id, 'opportunity_step_id' => $open?->id,
                            'user_id' => auth()->id(), 'type' => $data['type'], 'subject' => $data['subject'], 'body' => $data['body'] ?? null,
                            'due_at' => $data['due_at'], 'is_done' => false,
                        ]);
                        Notification::make()->title('فعالیت ثبت شد')->success()->send();
                    }),
                Action::make('reRefer')->label('')->tooltip('ارجاع مجدد')->icon('heroicon-o-user-plus')->iconButton()->color('gray')
                    ->visible(fn (Opportunity $r) => static::canEdit($r) && ! in_array($r->status, [Opportunity::STATUS_WON, Opportunity::STATUS_LOST], true))
                    ->modalHeading('ارجاع مجدد')
                    ->form([
                        Select::make('to_user_id')->label('ارجاع به')->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))->searchable()->required()->native(false),
                        Textarea::make('note')->label('یادداشت ارجاع')->rows(2),
                        Textarea::make('new_step')->label('گام جدید برای مقصد')->required()->rows(2),
                        DatePicker::make('new_step_due')->label('مهلت')->native(false)->default(now()->addDays(3))->required(),
                    ])
                    ->action(function (Opportunity $record, array $data): void {
                        $to = User::query()->find($data['to_user_id']);
                        if (! $to) { Notification::make()->title('کاربر یافت نشد')->danger()->send(); return; }
                        $open = OpportunityStep::query()->where('opportunity_id', $record->id)->where('is_open', true)->orderByDesc('id')->first();
                        if ($open) { $open->close(trim((string) ($data['note'] ?? 'ارجاع مجدد'))); }
                        OpportunityStep::query()->create([
                            'company_id' => $record->company_id, 'opportunity_id' => $record->id, 'assigned_user_id' => $to->id,
                            'title' => (string) $data['new_step'], 'due_at' => $data['new_step_due'], 'is_open' => true,
                            'sort_order' => (int) OpportunityStep::query()->where('opportunity_id', $record->id)->max('sort_order') + 1,
                        ]);
                        $record->followup_user_id = $to->id;
                        $record->next_step = $data['new_step'];
                        $record->next_step_due = $data['new_step_due'];
                        $record->save();
                        Activity::query()->create([
                            'company_id' => $record->company_id, 'opportunity_id' => $record->id, 'user_id' => auth()->id(),
                            'type' => Activity::TYPE_NOTE, 'subject' => 'ارجاع مجدد به '.$to->name, 'body' => $data['note'] ?? null,
                            'due_at' => now(), 'is_done' => true, 'completed_at' => now(),
                        ]);
                        Notification::make()->title('ارجاع انجام شد')->success()->send();
                    }),
                Action::make('returnToOwner')->label('')->tooltip('بازگشت به مالک')->icon('heroicon-o-arrow-uturn-right')->iconButton()->color('gray')
                    ->visible(fn (Opportunity $r) => static::canEdit($r) && $r->followup_user_id && $r->owner_user_id && (int) $r->followup_user_id !== (int) $r->owner_user_id)
                    ->requiresConfirmation()->modalHeading('بازگشت پیگیری به مالک؟')
                    ->action(function (Opportunity $record): void {
                        $record->followup_user_id = $record->owner_user_id;
                        $record->save();
                        Activity::query()->create([
                            'company_id' => $record->company_id, 'opportunity_id' => $record->id, 'user_id' => auth()->id(),
                            'type' => Activity::TYPE_NOTE, 'subject' => 'بازگشت پیگیری به مالک',
                            'due_at' => now(), 'is_done' => true, 'completed_at' => now(),
                        ]);
                        Notification::make()->title('پیگیری به مالک برگشت')->success()->send();
                    }),
                RestoreAction::make()->label('')->tooltip('بازگردانی')->iconButton(),
                ForceDeleteAction::make()->label('')->tooltip('حذف دائم')->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make()->label('بازگردانی'),
                    ForceDeleteBulkAction::make()->label('حذف دائم'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [ActivitiesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpportunities::route('/'),
            'create' => Pages\CreateOpportunity::route('/create'),
            'quick-create' => Pages\QuickCreateOpportunity::route('/quick-create'),
            'edit' => Pages\EditOpportunity::route('/{record}/edit'),
            'view' => Pages\ManageOpportunity::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['stage', 'owner', 'followupUser']);
        $user = auth()->user();
        if (! $user) { return $query->whereRaw('1 = 0'); }
        if (method_exists($user, 'isCompanyAdmin') && $user->isCompanyAdmin()) { return $query; }
        $visibleIds = method_exists($user, 'managedUserIds') ? $user->managedUserIds(includeSelf: true)->all() : [(int) $user->id];
        if ($visibleIds === []) { $visibleIds = [(int) $user->id]; }
        return $query->where(function (Builder $q) use ($visibleIds): void {
            $q->whereIn('owner_user_id', $visibleIds)->orWhereIn('followup_user_id', $visibleIds);
        });
    }

    public static function canView($record): bool
    {
        if (! parent::canView($record)) { return false; }
        $user = auth()->user();
        if (! $user) { return false; }
        if (method_exists($user, 'isCompanyAdmin') && $user->isCompanyAdmin()) { return true; }
        $visibleIds = method_exists($user, 'managedUserIds') ? $user->managedUserIds(includeSelf: true) : collect([(int) $user->id]);
        return $visibleIds->contains((int) ($record->owner_user_id ?? 0)) || $visibleIds->contains((int) ($record->followup_user_id ?? 0));
    }

    public static function canEdit($record): bool
    {
        if (! parent::canEdit($record)) { return false; }
        return static::canView($record);
    }
}
