<?php

namespace App\Filament\Resources\QuoteResource\RelationManagers;

use App\Models\ProductModel;
use App\Services\CatalogConfigurator;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use InvalidArgumentException;

class QuoteLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static ?string $title = 'خطوط پیش‌فاکتور';

    public function form(Schema $schema): Schema
    {
        $companyId = (int) (session('current_company_id') ?? 0);

        return $schema->components([
            Select::make('product_type')
                ->label('نوع محصول')
                ->options([
                    'notopia' => 'نوتوپیا',
                    'cheleva' => 'چلیوا',
                    'service' => 'خدمات',
                    'other' => 'سایر',
                ])
                ->default('notopia')
                ->required()
                ->native(false)
                ->live()
                ->afterStateUpdated(function (Set $set): void {
                    $set('product_model_id', null);
                    $set('size_id', null);
                    $set('cover_sku_id', null);
                    $set('option_ids', []);
                }),

            Select::make('product_model_id')
                ->label('مدل کاتالوگ')
                ->options(function () use ($companyId) {
                    return app(CatalogConfigurator::class)->modelOptions($companyId);
                })
                ->searchable()
                ->native(false)
                ->visible(fn (Get $get) => in_array($get('product_type'), ['notopia', 'cheleva', null], true))
                ->live()
                ->afterStateUpdated(function ($state, Set $set) use ($companyId): void {
                    $set('size_id', null);
                    $set('cover_sku_id', null);
                    $set('option_ids', []);

                    if (! $state) {
                        return;
                    }

                    $model = ProductModel::query()->find($state);
                    if (! $model) {
                        return;
                    }

                    $set('title', trim(($model->code ? $model->code.' — ' : '').$model->name));
                    if ((float) ($model->base_price ?? 0) > 0) {
                        $set('unit_price', (float) $model->base_price);
                    }

                    // prefetch schema side-effect free for clarity
                    app(CatalogConfigurator::class)->schema($companyId, (int) $state);
                }),

            Select::make('size_id')
                ->label('سایز مجاز')
                ->options(function (Get $get) use ($companyId) {
                    $modelId = $get('product_model_id');
                    if (! $modelId) {
                        return [];
                    }
                    $schema = app(CatalogConfigurator::class)->schema($companyId, (int) $modelId);

                    return collect($schema['sizes'])
                        ->mapWithKeys(fn (array $s) => [$s['id'] => $s['name'].($s['code'] ? ' ('.$s['code'].')' : '')])
                        ->all();
                })
                ->searchable()
                ->native(false)
                ->visible(fn (Get $get) => filled($get('product_model_id'))),

            Select::make('cover_sku_id')
                ->label('جلد مجاز')
                ->options(function (Get $get) use ($companyId) {
                    $modelId = $get('product_model_id');
                    if (! $modelId) {
                        return [];
                    }
                    $schema = app(CatalogConfigurator::class)->schema($companyId, (int) $modelId);

                    return collect($schema['cover_skus'])
                        ->mapWithKeys(fn (array $c) => [$c['id'] => $c['name'].($c['code'] ? ' ('.$c['code'].')' : '')])
                        ->all();
                })
                ->searchable()
                ->native(false)
                ->visible(fn (Get $get) => filled($get('product_model_id'))),

            Select::make('option_ids')
                ->label('اقلام مجاز')
                ->multiple()
                ->options(function (Get $get) use ($companyId) {
                    $modelId = $get('product_model_id');
                    if (! $modelId) {
                        return [];
                    }
                    $schema = app(CatalogConfigurator::class)->schema($companyId, (int) $modelId);

                    return collect($schema['options'])
                        ->mapWithKeys(function (array $o) {
                            $label = $o['name'];
                            if ($o['is_recommended'] ?? false) {
                                $label .= ' ★';
                            }

                            return [$o['id'] => $label];
                        })
                        ->all();
                })
                ->searchable()
                ->native(false)
                ->visible(fn (Get $get) => filled($get('product_model_id'))),

            TextInput::make('title')
                ->label('عنوان خط')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Textarea::make('description')
                ->label('شرح / پیکربندی')
                ->rows(2)
                ->columnSpanFull(),

            TextInput::make('qty')
                ->label('تعداد')
                ->numeric()
                ->default(1)
                ->required()
                ->minValue(0.01),

            TextInput::make('unit_price')
                ->label('قیمت واحد (ریال)')
                ->numeric()
                ->default(0)
                ->required()
                ->minValue(0),

            TextInput::make('sort_order')
                ->label('ترتیب')
                ->numeric()
                ->default(10),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('product_type')
                    ->label('نوع')
                    ->badge(),
                Tables\Columns\TextColumn::make('product_model_id')
                    ->label('مدل')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '—';
                        }
                        $m = ProductModel::query()->find($state);

                        return $m?->code ?? (string) $state;
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('config_snapshot.size.name')
                    ->label('سایز')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('config_snapshot.cover_sku.name')
                    ->label('جلد')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('qty')
                    ->label('تعداد')
                    ->numeric(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('قیمت واحد')
                    ->numeric(),
                Tables\Columns\TextColumn::make('line_total')
                    ->label('جمع خط')
                    ->numeric(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('خط جدید')
                    ->visible(fn () => $this->getOwnerRecord()->status !== 'converted')
                    ->mutateFormDataUsing(fn (array $data) => $this->validateLinePayload($data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('ویرایش')
                    ->visible(fn () => $this->getOwnerRecord()->status !== 'converted')
                    ->mutateFormDataUsing(fn (array $data) => $this->validateLinePayload($data))
                    ->mutateRecordDataUsing(function (array $data): array {
                        $snapshot = $data['config_snapshot'] ?? [];
                        $data['size_id'] = $snapshot['size']['id'] ?? null;
                        $data['cover_sku_id'] = $snapshot['cover_sku']['id'] ?? null;
                        $data['option_ids'] = collect($snapshot['options'] ?? [])->pluck('id')->all();

                        return $data;
                    }),
                DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn () => $this->getOwnerRecord()->status !== 'converted'),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateLinePayload(array $data): array
    {
        $companyId = (int) ($this->getOwnerRecord()->company_id ?? session('current_company_id') ?? 0);
        $configurator = app(CatalogConfigurator::class);

        $productType = (string) ($data['product_type'] ?? 'notopia');
        $modelId = isset($data['product_model_id']) ? (int) $data['product_model_id'] : null;

        $selection = [
            'size_id' => $data['size_id'] ?? null,
            'cover_sku_id' => $data['cover_sku_id'] ?? null,
            'option_ids' => $data['option_ids'] ?? [],
            'unit_price' => $data['unit_price'] ?? null,
            'require_published' => true,
        ];

        try {
            $snapshot = $configurator->snapshot($companyId, $productType, $modelId, $selection);
        } catch (InvalidArgumentException $e) {
            Notification::make()
                ->title('ترکیب نامعتبر')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }

        $data['config_snapshot'] = $snapshot;

        // virtual form fields are not columns
        unset($data['size_id'], $data['cover_sku_id'], $data['option_ids']);

        if (empty($data['description'])) {
            $parts = array_filter([
                $snapshot['size']['name'] ?? null,
                $snapshot['cover_sku']['name'] ?? null,
                collect($snapshot['options'] ?? [])->pluck('name')->implode('، '),
            ]);
            if ($parts !== []) {
                $data['description'] = implode(' | ', $parts);
            }
        }

        return $data;
    }
}
