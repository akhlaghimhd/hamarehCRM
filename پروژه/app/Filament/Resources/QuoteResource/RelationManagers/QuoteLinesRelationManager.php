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
                ->live(),

            Select::make('product_model_id')
                ->label('مدل کاتالوگ')
                ->options(function () use ($companyId) {
                    return app(CatalogConfigurator::class)->modelOptions($companyId);
                })
                ->searchable()
                ->native(false)
                ->visible(fn (Get $get) => in_array($get('product_type'), ['notopia', 'cheleva', null], true))
                ->live()
                ->afterStateUpdated(function ($state, Set $set) {
                    if (! $state) {
                        return;
                    }
                    $model = ProductModel::query()->find($state);
                    if (! $model) {
                        return;
                    }
                    $set('title', trim(($model->code ? $model->code . ' — ' : '') . $model->name));
                    if ((float) ($model->base_price ?? 0) > 0) {
                        $set('unit_price', (float) $model->base_price);
                    }
                }),

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
                    ->mutateFormDataUsing(fn (array $data) => $this->validateLinePayload($data)),
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

        try {
            $configurator->validate(
                $companyId,
                (string) ($data['product_type'] ?? 'notopia'),
                isset($data['product_model_id']) ? (int) $data['product_model_id'] : null,
                [
                    'unit_price' => $data['unit_price'] ?? null,
                ]
            );
        } catch (InvalidArgumentException $e) {
            Notification::make()
                ->title('ترکیب نامعتبر')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw $e;
        }

        return $data;
    }
}
