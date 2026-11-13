<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\ProductModelResource\Pages;
use App\Models\ProductLine;
use App\Models\ProductModel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use App\Support\Fa;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\BulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use App\Models\CatalogSeason;
use App\Models\CoverSku;
use App\Models\ProductFamily;
use App\Models\ProductOption;
use App\Models\ProductSize;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Illuminate\Support\Str;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class ProductModelResource extends Resource
{
public static function shouldRegisterNavigation(): bool
    {
        return true;
    }


    use ChecksResourcePermission;
protected static ?string $permissionView = 'products.view';
    protected static ?string $permissionManage = 'products.manage';
    protected static ?string $model = ProductModel::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube-transparent';
    protected static ?string $modelLabel = 'مدل محصول';
    protected static ?string $pluralModelLabel = 'مدل‌های محصول';
    protected static ?string $navigationLabel = 'مدل‌ها';

    protected static string|\UnitEnum|null $navigationGroup = 'نوتوپیا';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()
            ->withCount(['sizes', 'coverSkus', 'options'])
            ->with(['family', 'season']);

        $notopiaId = \App\Models\ProductLine::query()->where('code', 'notopia')->value('id');
        if ($notopiaId) {
            $query->where('product_line_id', $notopiaId);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('product_line_id')
                ->default(fn () => ProductLine::query()->where('code', 'notopia')->value('id'))
                ->dehydrated(),

            Section::make('مشخصات مدل')
                ->description('کد سیستمی از خانواده + نام + سال پیشنهاد می‌شود')
                ->collapsible()
                ->schema([
                    Select::make('product_family_id')
                        ->label('خانواده')
                        ->options(fn () => ProductFamily::query()
                            ->where('is_active', true)
                            ->orderBy('sort')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::suggestCode($get, $set)),

                    Select::make('catalog_season_id')
                        ->label('کالکشن / سال')
                        ->options(fn () => CatalogSeason::query()
                            ->where('is_active', true)
                            ->orderBy('sort')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::suggestCode($get, $set)),

                    TextInput::make('name')
                        ->label('نام مدل')
                        ->placeholder('مثلاً: زیبا')
                        ->required()
                        ->maxLength(100)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::suggestCode($get, $set)),

                    TextInput::make('code')
                        ->label('کد سیستمی')
                        ->required()
                        ->maxLength(50)
                        ->unique(ignoreRecord: true)
                        ->helperText('پیشنهاد خودکار — قابل ویرایش'),

                    TextInput::make('base_price')
                        ->label('قیمت پایه (ریال)')
                        ->numeric()
                        ->minValue(0),

                    Select::make('status')
                        ->label('وضعیت انتشار')
                        ->options([
                            'draft'     => 'پیش‌نویس',
                            'published' => 'منتشر (قابل سفارش)',
                            'archived'  => 'بایگانی',
                        ])
                        ->required()
                        ->default('draft')
                        ->native(false),

                    TextInput::make('sort')
                        ->label('ترتیب نمایش')
                        ->numeric()
                        ->default(0),

                    Textarea::make('internal_note')
                        ->label('یادداشت داخلی')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 2,
                ]),

            Section::make('ماتریس مجاز')
                ->description('لیست اجازه برای سفارش — مشتری در هر سفارش فقط یک سایز و یک جلد انتخاب می‌کند')
                ->icon('heroicon-o-squares-2x2')
                ->collapsible()
                ->schema([
                    Select::make('sizes')
                        ->label('سایزهای مجاز')
                        ->multiple()
                        ->relationship('sizes', 'name')
                        ->preload()
                        ->searchable()
                        ->native(false)
                        ->required()
                        ->minItems(1)
                        ->helperText('حداقل یک سایز — در سفارش فقط یکی انتخاب می‌شود')
                        ->columnSpanFull(),

                    Select::make('coverSkus')
                        ->label('جلد / رنگ‌های مجاز')
                        ->multiple()
                        ->relationship('coverSkus', 'name')
                        ->preload()
                        ->searchable()
                        ->native(false)
                        ->required()
                        ->minItems(1)
                        ->helperText('حداقل یک جلد/رنگ — در سفارش فقط یکی انتخاب می‌شود')
                        ->columnSpanFull(),

                    Select::make('options')
                        ->label('اقلام همراه مجاز')
                        ->multiple()
                        ->relationship('options', 'name')
                        ->preload()
                        ->searchable()
                        ->native(false)
                        ->helperText('اختیاری — خالی = بدون اقلام')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function suggestCode(Get $get, Set $set): void
    {
        $familyId = $get('product_family_id');
        $seasonId = $get('catalog_season_id');
        $name = trim((string) $get('name'));
        if (! $familyId || ! $seasonId || $name === '') {
            return;
        }
        $family = ProductFamily::query()->find($familyId);
        $season = CatalogSeason::query()->find($seasonId);
        if (! $family || ! $season) {
            return;
        }
        $familyPart = Str::upper(Str::slug((string) ($family->code ?: $family->name), '-'));
        $namePart = Str::upper(Str::slug($name, '-'));
        $yearPart = preg_replace('/[^0-9A-Za-z\-]/u', '', (string) ($season->year_label ?: $season->code)) ?: $season->code;
        $set('code', "{$familyPart}-{$namePart}-{$yearPart}");
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordAction(null)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record?->code)
                    ->url(fn ($record) => static::getUrl('edit', ['record' => $record]))
                    ->color('primary')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('family.name')
                    ->label('خانواده')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('season.name')
                    ->label('کالکشن')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('انتشار')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'draft'     => 'پیش‌نویس',
                        'published' => 'منتشر',
                        'archived'  => 'بایگانی',
                        default     => $state ?? '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'draft'     => 'warning',
                        'published' => 'success',
                        'archived'  => 'gray',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('base_price')
                    ->label('قیمت پایه')
                    ->formatStateUsing(fn ($state) => $state !== null ? \App\Support\Fa::number($state, 0) : '—')
                    ->alignEnd()
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('cover_skus_count')
                    ->label('رنگ/جلد')
                    ->counts('coverSkus')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('options_count')
                    ->label('اقلام')
                    ->counts('options')
                    ->alignCenter()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('وضعیت')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->defaultSort('sort')
            ->filters([
                Tables\Filters\TrashedFilter::make()->label('حذف‌شده‌ها'),
            ])
            ->actions([
                \Filament\Actions\Action::make('duplicate')
                    ->label('کپی')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function ($record) {
                        $copy = $record->replicate();
                        $copy->name = $record->name . ' (کپی)';
                        $copy->code = $record->code . '-COPY';
                        $copy->status = 'draft';
                        $copy->is_active = true;
                        $copy->save();

                        $copy->sizes()->sync($record->sizes()->pluck($record->sizes()->getRelated()->getQualifiedKeyName())->all());
                        $copy->coverSkus()->sync($record->coverSkus()->pluck($record->coverSkus()->getRelated()->getQualifiedKeyName())->all());
                        $copy->options()->sync($record->options()->pluck($record->options()->getRelated()->getQualifiedKeyName())->all());
                    }),

                \Filament\Actions\EditAction::make()->label('ویرایش'),
                \Filament\Actions\DeleteAction::make()->label('حذف'),
                \Filament\Actions\RestoreAction::make()->label('بازگردانی'),
                \Filament\Actions\ForceDeleteAction::make()->label('حذف دائم'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()->label('حذف'),
                    \Filament\Actions\RestoreBulkAction::make()->label('بازگردانی'),
                    \Filament\Actions\ForceDeleteBulkAction::make()->label('حذف دائم'),
                ]),
            ])
            ->emptyStateHeading('هنوز مدلی ثبت نشده')
            ->emptyStateDescription('اولین مدل محصول را ایجاد کنید.')
            ->emptyStateActions([
                \Filament\Actions\CreateAction::make()->label('ایجاد مدل'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductModels::route('/'),
            'create' => Pages\CreateProductModel::route('/create'),
            'edit' => Pages\EditProductModel::route('/{record}/edit'),
        ];
    }
}
