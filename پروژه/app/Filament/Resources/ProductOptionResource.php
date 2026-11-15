<?php

namespace App\Filament\Resources;

use Filament\Tables\Columns\IconColumn;

use Filament\Tables\Enums\FiltersLayout;

use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Columns\ToggleColumn;
use App\Support\Fa;
use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\ProductOptionResource\Pages;
use App\Filament\Resources\ProductOptionResource\RelationManagers\VariantsRelationManager;
use App\Models\ProductLine;
use App\Models\ProductModel;
use App\Models\ProductOption;
use Filament\Actions\EditAction;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use UnitEnum;

class ProductOptionResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'products.view';
    protected static ?string $permissionManage = 'products.manage';
    protected static ?string $model = ProductOption::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static ?string $navigationLabel = 'اقلام و آپشن‌ها';
    protected static ?string $modelLabel = 'قلم';
    protected static ?string $pluralModelLabel = 'اقلام و آپشن‌ها';
    protected static string|UnitEnum|null $navigationGroup = 'نوتوپیا';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();
        $id = ProductLine::query()->where('code', 'notopia')->value('id');
        if ($id) {
            $q->where('product_line_id', $id);
        }

        return $q;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('option_tabs')
                ->tabs([
                    Tab::make('basic')
                        ->label('۱. مشخصات قلم')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            Section::make()
                                ->description('نام و کد این لوازم جانبی را مشخص کنید.')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('نام قابل‌نمایش')
                                        ->placeholder('مثلاً: خودکار')
                                        ->required()
                                        ->maxLength(150)
                                        ->columnSpan(2),
                                    TextInput::make('code')
                                        ->label('کد سیستمی')
                                        ->placeholder('مثلاً: pen')
                                        ->required()
                                        ->maxLength(50)
                                        ->helperText('انگلیسی، یکتا'),
                                    Select::make('type')
                                        ->label('نوع قلم')
                                        ->options([
                                            'addon' => 'لوازم جانبی (خودکار، دستمال، کیف، …)',
                                            'nfc' => 'NFC',
                                            'software' => 'نرم‌افزار',
                                            'other' => 'سایر',
                                        ])
                                        ->required()
                                        ->default('addon'),
                                    Toggle::make('is_active')
                                        ->label('فعال در سیستم')
                                        ->default(true)
                                        ->inline(false),
                                    TextInput::make('sort')
                                        ->label('ترتیب نمایش')
                                        ->numeric()
                                        ->default(0),
                                ])
                                ->columns(2),
                        ]),

                    Tab::make('models')
                        ->label('۲. مدل‌های مجاز')
                        ->icon('heroicon-o-squares-2x2')
                        ->schema([
                            Section::make('کدام مدل‌های نوتوپیا می‌توانند این قلم را داشته باشند؟')
                                ->description('مثلاً دستمال فقط برای مانا؛ کیف برای مانا و در صورت نیاز کیوان.')
                                ->schema([
                                    Select::make('model_scope')
                                        ->label('محدوده')
                                        ->options([
                                            'all' => 'همه مدل‌ها',
                                            'selected' => 'فقط مدل‌های انتخاب‌شده',
                                        ])
                                        ->default('all')
                                        ->live()
                                        ->required()
                                        ->native(false),
                                    Select::make('allowed_model_ids')
                                        ->label('انتخاب مدل‌ها')
                                        ->multiple()
                                        ->options(fn () => ProductModel::query()
                                            ->where('product_line_id', ProductLine::query()->where('code', 'notopia')->value('id'))
                                            ->orderBy('sort')
                                            ->pluck('name', 'id'))
                                        ->visible(fn (Get $get) => $get('model_scope') === 'selected')
                                        ->required(fn (Get $get) => $get('model_scope') === 'selected')
                                        ->native(false)
                                        ->helperText('مدل‌هایی که این قلم برایشان مجاز است'),
                                ]),
                        ]),

                    Tab::make('attributes')
                        ->label('۳. ویژگی‌های قابل‌انتخاب')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema([
                            Placeholder::make('attr_help')->hiddenLabel()
                                ->content(new HtmlString(
                                    '<div class="text-sm text-gray-600 dark:text-gray-300 space-y-1 leading-7">'
                                    .'<p><strong>اینجا فقط «چه چیزی قابل انتخاب است» را تعریف کنید.</strong></p>'
                                    .'<p>قیمت و موجودی را در تب بعد (یا پایین صفحه بعد از ذخیره) برای هر ترکیب بگذارید.</p>'
                                    .'<p>اگر برای یک ویژگی تیک <em>مؤثر در قیمت و موجودی</em> را بزنید، برای هر مقدارش می‌توانید موجودی/قیمت جدا داشته باشید (مثل هر رنگ خودکار).</p>'
                                    .'</div>'
                                )),
                            Repeater::make('customAttributes')
                                ->relationship()
                                ->hiddenLabel()
                                ->schema([
                                    TextInput::make('label')
                                        ->label('نام ویژگی')
                                        ->placeholder('مثلاً: رنگ')
                                        ->required()
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                            if (blank($get('key')) && filled($state)) {
                                                $set('key', Str::slug($state, '_'));
                                            }
                                        }),
                                    TextInput::make('key')
                                        ->label('کلید')
                                        ->placeholder('color')
                                        ->required()
                                        ->maxLength(50)
                                        ->helperText('انگلیسی؛ برای سیستم'),
                                    Select::make('value_type')
                                        ->label('چطور انتخاب می‌شود؟')
                                        ->options([
                                            'select' => 'از روی لیست (یک انتخاب)',
                                            'multiselect' => 'از روی لیست (چند انتخاب)',
                                            'text' => 'متن آزاد',
                                            'number' => 'عدد',
                                            'boolean' => 'بله / خیر',
                                            'color' => 'انتخاب رنگ',
                                        ])
                                        ->default('select')
                                        ->required()
                                        ->live()
                                        ->native(false)
                                        ->selectablePlaceholder(false),
                                    Textarea::make('options_text')
                                        ->label('گزینه‌های لیست')
                                        ->placeholder("آبی\nقرمز\nبنفش")
                                        ->rows(4)
                                        ->visible(fn (Get $get) => in_array($get('value_type'), ['select', 'multiselect'], true))
                                        ->helperText('هر خط = یک گزینه')
                                        ->dehydrated(false)
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $lines = array_values(array_filter(array_map(
                                                'trim',
                                                preg_split('/\r\n|\r|\n/', (string) $state) ?: []
                                            )));
                                            $set('options_json', $lines);
                                        })
                                        ->afterStateHydrated(function (Textarea $component, $state, $record) {
                                            if ($record && is_array($record->options_json)) {
                                                $component->state(implode("\n", $record->options_json));
                                            }
                                        }),
                                    \Filament\Forms\Components\Hidden::make('options_json'),
                                    Toggle::make('affects_inventory')
                                        ->label('مؤثر در قیمت و موجودی')
                                        ->helperText('روشن = برای هر گزینه می‌توان قیمت/موجودی جدا گذاشت')
                                        ->default(false)
                                        ->inline(false),
                                    Toggle::make('is_required')
                                        ->label('انتخاب این ویژگی اجباری است')
                                        ->default(false)
                                        ->inline(false),
                                    TextInput::make('sort')
                                        ->label('ترتیب')
                                        ->numeric()
                                        ->default(0),
                                ])
                                ->columns(2)
                                ->collapsible()
                                ->cloneable()
                                ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'ویژگی جدید')
                                ->addActionLabel('+ افزودن ویژگی')
                                ->reorderable('sort')
                                ->defaultItems(0)
                                ->columnSpanFull(),
                        ]),

                    Tab::make('defaults')
                        ->label('۴. پیش‌فرض کلی')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Placeholder::make('price_help')->hiddenLabel()
                                ->content(new HtmlString(
                                    '<div class="text-sm text-gray-600 dark:text-gray-300 leading-7">'
                                    .'اگر قیمت و موجودی را <strong>برای هر ترکیب</strong> (مثلاً هر رنگ) جدا می‌گذارید، همان اولویت دارد. '
                                    .'مقادیر زیر فقط وقتی استفاده می‌شوند که ترکیبی تعریف نشده باشد.'
                                    .'</div>'
                                )),
                            Section::make()->schema([
                                TextInput::make('unit_price')
                                    ->label('قیمت واحد پیش‌فرض (ریال)')
                                    ->numeric()
                                    ->prefix('ریال'),
                                TextInput::make('stock_qty')
                                    ->label('موجودی پیش‌فرض')
                                    ->numeric()
                                    ->default(0),
                            ])->columns(2),
                        ]),
                ])
                ->columnSpanFull()
                ->persistTabInQueryString(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->weight('medium')
                    ->description(fn ($record) => $record?->code)
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
                    ->color('primary'),

                // یک ستون فشرده به‌جای دو ستون تگ‌دار پهن
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع')
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'addon' => 'لوازم جانبی',
                        'nfc' => 'NFC',
                        'software' => 'نرم‌افزار',
                        'other' => 'سایر',
                        default => $state ?: '—',
                    })
                    ->color(fn ($state) => match ($state) {
                        'addon' => 'gray',
                        'nfc' => 'info',
                        'software' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('model_scope')
                    ->label('مدل')
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'all' ? 'همه مدل‌ها' : 'انتخابی')
                    ->color(fn ($state) => $state === 'all' ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('custom_attributes_count')
                    ->counts('customAttributes')
                    ->label('ویژگی')
                    ->sortable()
                    ->toggleable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => Fa::digits($state)),

                Tables\Columns\TextColumn::make('variants_count')
                    ->counts('variants')
                    ->label('ترکیب')
                    ->sortable()
                    ->toggleable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => Fa::digits($state)),

                Tables\Columns\TextColumn::make('unit_price')
                    ->label('قیمت')
                    ->sortable()
                    ->toggleable()
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state === null || $state === '' ? '—' : Fa::number($state, 0)),

                Tables\Columns\TextColumn::make('stock_qty')
                    ->label('موجودی')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => Fa::digits($state ?? 0)),

                // آیکن وضعیت — بدون عنوان ستون شلوغ
                IconColumn::make('is_active')
                    ->label('وضعیت')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-no-symbol')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable()
                    ->toggleable()
                    ->tooltip(fn ($record) => $record->is_active
                        ? 'وضعیت فعلی: فعال — برای غیرفعال کردن کلیک کنید'
                        : 'وضعیت فعلی: غیرفعال — برای فعال کردن کلیک کنید')
                    ->action(
                        \Filament\Actions\Action::make('toggleActive')
                            ->requiresConfirmation()
                            ->modalHeading(fn ($record) => $record->is_active ? 'غیرفعال کردن قلم' : 'فعال کردن قلم')
                            ->modalDescription(
                                'یادآوری: پس از راه‌اندازی ماژول سفارش، اگر این قلم در سفارش باز باشد تغییر وضعیت مسدود می‌شود. فعلاً فقط تأیید دستی است.'
                            )
                            ->modalSubmitActionLabel('تأیید')
                            ->action(function ($record) {
                                // TODO: سفارش باز — بعد از ماژول سفارش تکمیل شود
                                $record->update(['is_active' => ! $record->is_active]);
                            })
                    ),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخرین تغییر')
                    ->formatStateUsing(fn ($state) => Fa::dateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('حذف شده در')
                    ->formatStateUsing(fn ($state) => Fa::dateTime($state))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
            ])
            ->defaultSort('sort')
            ->striped()
            ->paginated([10, 15, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            // فیلترها بیرون از کشو — بالای جدول
            ->filters([
                TrashedFilter::make()
                    ->label('حذف‌شده‌ها'),
                TernaryFilter::make('is_active')
                    ->label('وضعیت فعال')
                    ->trueLabel('فقط فعال')
                    ->falseLabel('فقط غیرفعال')
                    ->placeholder('همه'),
                SelectFilter::make('type')
                    ->label('نوع قلم')
                    ->options([
                        'addon' => 'لوازم جانبی',
                        'nfc' => 'NFC',
                        'software' => 'نرم‌افزار',
                        'other' => 'سایر',
                    ]),
                SelectFilter::make('model_scope')
                    ->label('محدوده مدل')
                    ->options([
                        'all' => 'همه مدل‌ها',
                        'selected' => 'انتخابی',
                    ]),
            ])
            ->emptyStateHeading('قلم‌ای ثبت نشده')
            ->emptyStateDescription('خودکار، دستمال، کیف و … را اینجا تعریف کنید.')
            ->emptyStateActions([
                \Filament\Actions\CreateAction::make()
                    ->label('ثبت قلم جدید')
                    ->icon('heroicon-o-plus'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('ویرایش و موجودی')
                    ->visible(fn ($record) => static::canEdit($record) && ! $record->trashed()),
                RestoreAction::make()
                    ->label('بازگردانی')
                    ->visible(fn ($record) => $record->trashed()),
                ForceDeleteAction::make()
                    ->label('حذف دائم')
                    ->visible(fn ($record) => $record->trashed()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف (قابل بازگشت)'),
                    RestoreBulkAction::make()->label('بازگردانی'),
                    ForceDeleteBulkAction::make()->label('حذف دائم'),
                    BulkAction::make('activate')
                        ->label('فعال‌سازی')
                        ->icon('heroicon-o-check-circle')
                        ->requiresConfirmation()
                        ->modalHeading('فعال‌سازی موارد انتخاب‌شده')
                        ->modalDescription('یادآوری: بعداً اگر قلم در سفارش باز باشد، فعال/غیرفعال مسدود می‌شود. فعلاً فقط تأیید دستی است.')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label('غیرفعال‌سازی')
                        ->icon('heroicon-o-no-symbol')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('غیرفعال‌سازی موارد انتخاب‌شده')
                        ->modalDescription('یادآوری: پس از ماژول سفارش، قلمِ داخل سفارش باز غیرفعال نمی‌شود. فعلاً تأیید دستی است.')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('scope_all')
                        ->label('مجاز برای همه مدل‌ها')
                        ->icon('heroicon-o-squares-2x2')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['model_scope' => 'all']))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }


    public static function getRelations(): array
    {
        return [
            VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductOptions::route('/'),
            'create' => Pages\CreateProductOption::route('/create'),
            'edit' => Pages\EditProductOption::route('/{record}/edit'),
        ];
    }
}