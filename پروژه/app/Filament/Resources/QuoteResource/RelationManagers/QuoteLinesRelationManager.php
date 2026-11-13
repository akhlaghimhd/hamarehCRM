<?php

namespace App\Filament\Resources\QuoteResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class QuoteLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static ?string $title = 'خطوط پیش‌فاکتور';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('عنوان خط')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),
            Textarea::make('description')
                ->label('شرح / پیکربندی')
                ->rows(2)
                ->columnSpanFull(),
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
                ->native(false),
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
                    ->visible(fn () => $this->getOwnerRecord()->status !== 'converted'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('ویرایش')
                    ->visible(fn () => $this->getOwnerRecord()->status !== 'converted'),
                DeleteAction::make()
                    ->label('حذف')
                    ->visible(fn () => $this->getOwnerRecord()->status !== 'converted'),
            ]);
    }
}
